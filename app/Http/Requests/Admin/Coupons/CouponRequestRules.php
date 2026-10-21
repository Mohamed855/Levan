<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Coupons;

use Illuminate\Validation\Rule;

trait CouponRequestRules
{
    private function couponRules(mixed $coupon = null): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'alpha_dash', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon)], 'discount_type' => ['required', 'in:percentage,fixed'], 'discount_value' => ['required', 'numeric', 'min:0'], 'max_discount' => ['nullable', 'numeric', 'min:0'], 'expires_at' => ['nullable', 'date'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'is_active' => ['sometimes', 'boolean']];
    }
}
