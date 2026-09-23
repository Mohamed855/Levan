<?php

namespace App\Services\shared\Bills;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\Bills\Bill;
use Illuminate\Support\Facades\Auth;

class BillService
{
    // فاتورة مبدئية
    public function createPendingBill(float $amount, ?PaymentMethod $paymentMethod, float $originalAmount, BillType $type, array $couponData, array $metadata = []): Bill
    {
        return Bill::create([
            'amount'          => $amount,
            'original_amount' => $originalAmount,
            'discount_amount' => $couponData['discount_amount'],
            'coupon_id'       => $couponData['coupon_id'],
            'coupon_code'     => $couponData['coupon_code'],
            'coupon_value'    => $couponData['coupon_value'],
            'coupon_type'     => $couponData['coupon_type'],
            'type'            => $type,
            'payment_method'  => $paymentMethod,
            'status'          => BillStatus::PENDING,
            'due_date'       => now()->addDays(7),
            'metadata'        => $metadata,
        ]);
    }

    //
    public function markAsPaid(Bill $bill, string $tenantId, string $subscriptionId, PaymentMethod $paymentMethod, ?array $payment = null): void
    {
        $bill->update([
            'tenant_id'          => $tenantId,
            'subscription_id'    => $subscriptionId,
            'gateway_payment_id' => $payment['id'] ?? null,
            'status'             => BillStatus::PAID,
            'payment_method'     => $paymentMethod,
            'paid_at'            => now(),
        ]);
    }

    public function markAsVerified(Bill $bill): void
    {
        $bill->update([
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);
    }

    public function cancelBill(Bill $bill): void
    {
        $bill->update(['status' => BillStatus::CANCELLED]);
    }

    public function rejectBill(Bill $bill, string $reason): void
    {
        $bill->update([
            'status'         => BillStatus::UNPAID,
            'reject_reason'  => $reason,
            'verified_by'    => Auth::id(),
            'verified_at'    => now(),

            'invoice_url'    => null,
            'bank_id'        => null,
            'payment_method' => null,
        ]);
    }
    /*
    |============================================================================
    |============================================================================
    |           في الاسف الدوال القديمة  وتحتاج للمراجعة
    |============================================================================
    |============================================================================
    */

    // public function verifyBill($bill)
    // {
    //     return DB::transaction(function () use ($bill) {
    //         $this->subscriptionManager->changeSubscriptionToActive($bill->tenant);

    //         $this->billingService->changBillToPaid($bill->id, null, PaymentMethod::BANK_TRANSFER);

    //         $bill->update([
    //             'verified_by' => Auth::id(),
    //             'verified_at' => now(),
    //         ]);
    //     } );
    // }



    // Get coupon statistics
    // public function getCouponStatistics(): array
    // {
    //     $baseQuery = Coupon::query();

    //     return [
    //         'total'    => (clone $baseQuery)->count(),
    //         'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
    //         'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
    //     ];
    // }
}
