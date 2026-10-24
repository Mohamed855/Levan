<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Enums\Admin\Bills\BillStatus;
use App\Models\Admin\Bill;
use App\Models\Admin\Coupon;
use App\Models\Admin\CouponRedemption;
use Illuminate\Validation\ValidationException;

final class CouponRedemptionService
{
    /** @param array<string,mixed> $pricing */
    public function reserve(Bill $bill, array $pricing): void
    {
        $couponId = $pricing['coupon_id'] ?? null;
        if (! $couponId) return;

        $coupon = Coupon::query()->lockForUpdate()->findOrFail($couponId);
        $activeReservations = CouponRedemption::query()->where('coupon_id', $coupon->getKey())->where('status', 'reserved')->where('expires_at', '>', now())->count();
        if (! $coupon->is_active || ($coupon->expires_at?->isPast()) || ($coupon->usage_limit !== null && $coupon->used_count + $activeReservations >= $coupon->usage_limit)) {
            throw ValidationException::withMessages(['coupon_code' => 'كود الخصم لم يعد متاحاً.']);
        }

        CouponRedemption::query()->create([
            'coupon_id' => $coupon->getKey(),
            'bill_id' => $bill->getKey(),
            'discount_amount' => $pricing['discount_amount'],
            'status' => 'reserved',
            'expires_at' => $bill->due_at,
        ]);
    }

    public function redeem(Bill $bill): void
    {
        if ($bill->status !== BillStatus::PAID) {
            throw ValidationException::withMessages(['coupon_code' => 'لا يمكن تسوية كود الخصم قبل تأكيد دفع الفاتورة.']);
        }

        $redemption = CouponRedemption::query()->where('bill_id', $bill->getKey())->lockForUpdate()->first();
        if (! $redemption || $redemption->status === 'redeemed') return;
        if ($redemption->status !== 'reserved') {
            throw ValidationException::withMessages(['coupon_code' => 'حالة حجز كود الخصم غير صالحة.']);
        }

        $coupon = Coupon::query()->lockForUpdate()->findOrFail($redemption->coupon_id);
        $coupon->increment('used_count');
        $redemption->update(['status' => 'redeemed', 'redeemed_at' => now()]);
    }
}
