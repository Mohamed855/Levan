<?php

declare(strict_types=1);

namespace App\Actions\Web;

use App\Actions\Admin\Tenants\ProvisionTenantAction;
use App\Contracts\Payments\PaidCheckoutProvisioner;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class ProvisionPaidCheckoutAction implements PaidCheckoutProvisioner
{
    public function __construct(private readonly ProvisionTenantAction $provisionTenant) {}

    public function execute(PaymentAttempt $attempt): Tenant
    {
        $attempt = DB::transaction(function () use ($attempt): PaymentAttempt {
            $locked = PaymentAttempt::query()->with('bill')->lockForUpdate()->findOrFail($attempt->getKey());
            if (! in_array($locked->provider, ['moyasar', 'bank_transfer'], true) || ! in_array($locked->status, ['paid_pending_provision', 'provisioning', 'provisioned'], true)) {
                throw new RuntimeException('محاولة الدفع ليست جاهزة لإنشاء الاشتراك.');
            }

            if ($locked->processed_at !== null && $locked->bill?->tenant_id) {
                return $locked;
            }

            $locked->update(['status' => 'provisioning', 'failure_reason' => null]);

            return $locked->fresh('bill');
        });

        if ($attempt->processed_at !== null && $attempt->bill?->tenant_id) {
            return Tenant::query()->findOrFail($attempt->bill->tenant_id);
        }

        try {
            $tenant = $this->provisionTenant->execute($this->provisioningData($attempt), function (Tenant $tenant) use ($attempt): void {
                DB::transaction(function () use ($attempt, $tenant): void {
                    $bill = Bill::query()->lockForUpdate()->findOrFail($attempt->bill_id);
                    $metadata = $bill->metadata ?? [];
                    $metadata['tenant_id'] = $tenant->getKey();
                    $bill->update(['tenant_id' => $tenant->getKey(), 'metadata' => $metadata]);
                });
            });

            DB::transaction(function () use ($attempt, $tenant): void {
                $locked = PaymentAttempt::query()->with('bill')->lockForUpdate()->findOrFail($attempt->getKey());
                $bill = $locked->bill;
                $metadata = $bill->metadata ?? [];
                $metadata['tenant_id'] = $tenant->getKey();
                $bill->update(['tenant_id' => $tenant->getKey(), 'metadata' => $metadata]);
                $locked->update(['status' => 'provisioned', 'processed_at' => now(), 'failure_reason' => null]);
            });

            return $tenant;
        } catch (Throwable $exception) {
            PaymentAttempt::query()->whereKey($attempt->getKey())->update([
                'status' => 'paid_pending_provision',
                'failure_reason' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function provisioningData(PaymentAttempt $attempt): array
    {
        $metadata = $attempt->bill?->metadata;
        if (! is_array($metadata) || ($metadata['checkout_source'] ?? null) !== 'site_checkout') {
            throw new RuntimeException('بيانات إنشاء الاشتراك غير مكتملة.');
        }

        $provisioning = $metadata['provisioning'] ?? $metadata;
        if (! is_array($provisioning)) {
            throw new RuntimeException('بيانات إنشاء الاشتراك غير مكتملة.');
        }

        foreach (['billing_company_name', 'billing_commercial_number', 'billing_name', 'billing_email', 'billing_phone', 'billing_password_hash'] as $key) {
            if (empty($provisioning[$key])) {
                throw new RuntimeException('بيانات إنشاء الاشتراك غير مكتملة.');
            }
        }

        return [
            'plan_id' => $metadata['plan_id'],
            'company_name' => $provisioning['billing_company_name'],
            'commercial_number' => $provisioning['billing_commercial_number'],
            'tax_number' => $provisioning['billing_tax_number'] ?? null,
            'city_id' => $provisioning['billing_city_id'] ?? null,
            'address' => $provisioning['billing_address'] ?? null,
            'primary_contact_name' => $provisioning['billing_name'],
            'primary_contact_email' => $provisioning['billing_email'],
            'primary_contact_phone' => $provisioning['billing_phone'],
            'password' => $provisioning['billing_password_hash'],
            'price' => (float) $attempt->amount,
            'subscription_status' => 'active',
        ];
    }
}
