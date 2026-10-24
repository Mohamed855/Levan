<?php

declare(strict_types=1);

namespace App\Actions\Admin\Bills;

use App\Contracts\Payments\PaidCheckoutProvisioner;
use App\Actions\Tenant\Subscriptions\FulfillTenantCommercialPaymentAction;
use App\Enums\Admin\Bills\BillStatus;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Services\Web\CouponRedemptionService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ApproveBankTransferAction
{
    public function __construct(
        private readonly PaidCheckoutProvisioner $provisioner,
        private readonly FulfillTenantCommercialPaymentAction $fulfillTenantCommercialPayment,
        private readonly CouponRedemptionService $coupons,
    ) {}

    public function execute(Bill $bill, AdminUser $admin): ?PaymentAttempt
    {
        $attempt = DB::transaction(function () use ($bill, $admin): ?PaymentAttempt {
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->getKey());
            $attempt = PaymentAttempt::query()
                ->where('bill_id', $lockedBill->getKey())
                ->where('provider', 'bank_transfer')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($lockedBill->status !== BillStatus::PENDING_VERIFICATION || ! $attempt || $attempt->status !== 'pending_verification') {
                throw new RuntimeException('لا توجد حوالة بنكية معلقة قابلة للاعتماد.');
            }

            $metadata = $lockedBill->metadata ?? [];
            $metadata['history'][] = [
                'from_status' => BillStatus::PENDING_VERIFICATION->value,
                'to_status' => BillStatus::PAID->value,
                'notes' => 'تم اعتماد التحويل البنكي.',
                'admin_id' => $admin->getKey(),
                'admin_name' => $admin->name,
                'changed_at' => now()->toIso8601String(),
            ];
            $lockedBill->update([
                'status' => BillStatus::PAID,
                'settled_amount' => $lockedBill->amount,
                'paid_at' => now(),
                'metadata' => $metadata,
            ]);
            $attempt->update(['status' => 'paid_pending_provision', 'paid_at' => now()]);
            $this->coupons->redeem($lockedBill);

            return $attempt->fresh('bill');
        });

        $source = $attempt?->bill?->metadata['checkout_source'] ?? null;
        if ($source === 'tenant_commercial') {
            $this->fulfillTenantCommercialPayment->execute($attempt->bill);

            return $attempt->fresh('bill');
        }

        if ($source !== 'site_checkout') {
            return $attempt;
        }

        $this->provisioner->execute($attempt);

        return $attempt->fresh('bill');
    }
}
