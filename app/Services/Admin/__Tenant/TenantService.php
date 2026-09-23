<?php

namespace App\Services\Admin\Tenant;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Jobs\Admin\Mail\SendSubscriptionMailJob;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Subscription\SubscriptionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class __TenantService
{
    public function __construct(
        private TenantCreator       $tenantCreator,
        private TenantInitializer   $tenantInitializer,
        private SubscriptionManager $subscriptionManager,
        private BillService         $billService,
    ) {}
    // Get tenant statistics
    public function getTenantStatistics(): array
    {
        $baseQuery = Tenant::query();

        return [
            'total'                     => (clone $baseQuery)->count(),

            'with_active_subscription'  => (clone $baseQuery)->whereHas('lastSubscription', function ($q) {
                $q->where('status', SubscriptionStatus::ACTIVE);
            })->count(),

            'with_expired_subscription' => (clone $baseQuery)->whereHas('lastSubscription', function ($q) {
                $q->where('status', SubscriptionStatus::EXPIRED);
            })->count(),

            'without_subscription'      => (clone $baseQuery)->whereDoesntHave('lastSubscription')->count(),
        ];
    }

    // Create tenant
    public function create(array $data)
    {
        $metadata = [
            'plan_id'                   => $data['plan_id'],
            'billing_name'              => $data['name'],
            'billing_email'             => $data['email'],
            'billing_phone'             => $data['phone'],
            'billing_password'          => $data['password'],
            'billing_company_name'      => $data['company_name'],
            'billing_commercial_number' => $data['commercial_number'],
        ];

        $plan = Plan::findOrFail($data['plan_id']);

        [$tenant, $user] = $this->tenantCreator->createTenantEnvironment($metadata);

        [$subscriptionStatus, $periodStatus] = $this->resolveSubscriptionStatus($data['payment_type']);

        $subscription = $this->subscriptionManager->createSubscription(
            $tenant,
            $plan,
            $this->resolveAmount($data, $plan),
            $subscriptionStatus,
            $periodStatus
        );

        //  ربط المستخدم بالاشتراك
        $this->tenantInitializer->createCoreSeat($tenant, $subscription, $user);

        //  إنشاء الفاتورة
        $this->createBill($data, $plan, $subscription, $tenant);

        // إرسال الإيميل
        SendSubscriptionMailJob::dispatch($data['email'], $subscription, 'new')->onQueue('email');

        return $tenant;
    }

    // Update Tenant
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update([
                'company_name'      => $data['company_name']      ?? $tenant->company_name,
                'commercial_number' => $data['commercial_number'] ?? $tenant->commercial_number,
                'updated_by'        => auth()->id(),
            ]);

            $this->updateTenantData($tenant, $data);

            return $tenant->fresh();
        });
    }

    // Delete Tenant
    public function delete(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            return $tenant->delete();
        });
    }



    // /*
    // |============================================================================
    // |============================================================================
    // |                           Private Methods
    // |============================================================================
    // |============================================================================
    // */
    // private function resolveSubscriptionStatus(string $paymentType): array
    // {
    //     return match ($paymentType) {
    //         'paid'   => [SubscriptionStatus::ACTIVE,     SubscriptionPeriodStatus::ACTIVE],
    //         'unpaid' => [SubscriptionStatus::SUSPENDED,  SubscriptionPeriodStatus::PENDING_PAYMENT],
    //         'trial'  => [SubscriptionStatus::TRIAL,      SubscriptionPeriodStatus::TRIAL],
    //     };
    // }

    // private function resolveAmount(array $data, Plan $plan): float
    // {
    //     $base     = (float) $plan->price_after_discount;

    //     $discount = ($data['has_discount'] ?? 'no') === 'yes'
    //         ? (float) ($data['discount_value'] ?? 0)
    //         : 0;

    //     return $data['payment_type'] === 'trial' ? 0 : max(0, $base - $discount);
    // }

    // private function resolvePaymentMethod(array $data): PaymentMethod
    // {
    //     if ($data['payment_type'] !== 'paid') return PaymentMethod::ADMIN;

    //     return match ($data['payment_method'] ?? 'admin') {
    //         'bank_transfer' => PaymentMethod::BANK_TRANSFER,
    //         'online'        => PaymentMethod::ONLINE,
    //         'cash'          => PaymentMethod::CASH,
    //         default         => PaymentMethod::ADMIN,
    //     };
    // }


    // private function createBill(array $data, Plan $plan, $subscription, Tenant $tenant): void
    // {
    //     $discount   = ($data['has_discount'] ?? 'no') === 'yes' ? (float)($data['discount_value'] ?? 0) : 0;

    //     $couponData = [
    //         'discount_amount' => $discount,
    //         'coupon_id'       => null,
    //         'coupon_code'     => null,
    //         'coupon_value'    => null,
    //         'coupon_type'     => null,
    //     ];

    //     $bill = $this->billService->createPendingBill(
    //         amount: $this->resolveAmount($data, $plan),
    //         paymentMethod: $this->resolvePaymentMethod($data),
    //         originalAmount: (float) $plan->price_after_discount,
    //         type: BillType::NEW_SUBSCRIPTION,
    //         couponData: $couponData,
    //         metadata: [
    //             'plan_id'                   => $plan->id,
    //             'billing_name'              => $data['name'],
    //             'billing_email'             => $data['email'],
    //             'billing_phone'             => $data['phone'],
    //             'billing_company_name'      => $data['company_name'],
    //             'billing_commercial_number' => $data['commercial_number'],
    //         ]
    //     );

    //     match ($data['payment_type']) {
    //         'paid'   => $this->handlePaidBill($bill, $tenant, $subscription, $data),
    //         'unpaid' => $bill->update([
    //             'tenant_id'       => $tenant->id,
    //             'subscription_id' => $subscription->id,
    //             'status'          => BillStatus::UNPAID,
    //         ]),
    //         'trial'  => $bill->update([
    //             'tenant_id'       => $tenant->id,
    //             'subscription_id' => $subscription->id,
    //             'status'          => BillStatus::TRIAL,
    //         ]),
    //     };
    // }


    // private function handlePaidBill(Bill $bill, Tenant $tenant, $subscription, array $data): void
    // {
    //     $this->billService->markAsPaid(
    //         bill: $bill,
    //         tenantId: $tenant->id,
    //         subscriptionId: $subscription->id,
    //         paymentMethod: $this->resolvePaymentMethod($data),
    //         payment: null,
    //     );

    //     $updateData = [
    //         'verified_by' => Auth::id(),
    //         'verified_at' => now(),
    //     ];

    //     if (($data['has_discount'] ?? 'no') === 'yes') {
    //         $updateData['settlement_amount'] = $data['discount_value'];
    //     }

    //     if (!empty($data['receipt_path'])) {
    //         $updateData['invoice_url'] = $data['receipt_path'];
    //     }

    //     $bill->update($updateData);
    // }










    private function updateTenantData(Tenant $tenant, array $data): void
    {
        tenancy()->initialize($tenant);

        try {
            $this->updateFirstUser($data);

            $this->updateSettings($data);
        } finally {
            tenancy()->end();
        }
    }

    private function updateFirstUser(array $data): void
    {
        $user = User::where('id', '1')->first();

        if (!$user) {
            return;
        }

        $updateData = [
            'name'  => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
    }

    private function updateSettings(array $data): void
    {
        $settings = Settings::first();

        if (!$settings) {
            return;
        }

        $settings->update([
            'office_name' => $data['company_name'] ?? $settings->office_name,
        ]);
    }
}
