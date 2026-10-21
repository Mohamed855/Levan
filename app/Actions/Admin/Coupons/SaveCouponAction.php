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
        return Coupon::query()->create(Arr::except($data, 'is_active') + ['is_active' => (bool) ($data['is_active'] ?? false), 'created_by' => $creator->id]);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update(Arr::except($data, 'created_by'));
        return $coupon;
    }
}
