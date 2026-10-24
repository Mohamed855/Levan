<?php

declare(strict_types=1);

namespace App\Actions\Admin\Coupons;

use App\Models\Admin\Coupon;
use App\Models\Admin\AdminUser;
use Illuminate\Support\Arr;

final class SaveCouponAction
{
    public function create(array $data, AdminUser $creator): Coupon
    {
        $attributes = Arr::except($data, 'is_active');
        $attributes['code'] = mb_strtoupper(trim((string) $data['code']));
        $attributes['is_active'] = (bool) ($data['is_active'] ?? true);
        $attributes['created_by'] = $creator->id;

        return Coupon::query()->create($attributes);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        if (array_key_exists('code', $data)) {
            $data['code'] = mb_strtoupper(trim((string) $data['code']));
        }

        $coupon->update(Arr::except($data, 'created_by'));
        return $coupon;
    }
}
