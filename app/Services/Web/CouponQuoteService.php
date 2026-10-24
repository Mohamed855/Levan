<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Models\Admin\Coupon;
use App\Models\Admin\Plans\Plan;
use Illuminate\Validation\ValidationException;

final class CouponQuoteService
{
    /** @return array{coupon_id:int|null,coupon_code:string|null,base_amount:float,discount_amount:float,total_amount:float,tax_amount:float,subtotal_amount:float} */
    public function quote(Plan $plan, ?string $code = null): array
    {
        $baseAmount = round($plan->price_after_discount, 2);
        $coupon = $this->coupon($code);
        $discount = 0.0;

        if ($coupon) {
            $discount = $coupon->discount_type === 'percentage'
                ? $baseAmount * ((float) $coupon->discount_value / 100)
                : (float) $coupon->discount_value;
            if ($coupon->max_discount !== null) {
                $discount = min($discount, (float) $coupon->max_discount);
            }
            $discount = round(min($discount, $baseAmount), 2);
        }

        $total = round($baseAmount - $discount, 2);
        $tax = round($total * 0.15, 2);

        return [
            'coupon_id' => $coupon?->getKey(),
            'coupon_code' => $coupon?->code,
            'base_amount' => $baseAmount,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'tax_amount' => $tax,
            'subtotal_amount' => round($total - $tax, 2),
        ];
    }

    private function coupon(?string $code): ?Coupon
    {
        $code = mb_strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [$code])->first();
        if (! $coupon || ! $coupon->is_active || ($coupon->expires_at?->isPast()) || ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit)) {
            throw ValidationException::withMessages(['coupon_code' => 'كود الخصم غير صالح أو انتهت صلاحيته.']);
        }

        return $coupon;
    }
}
