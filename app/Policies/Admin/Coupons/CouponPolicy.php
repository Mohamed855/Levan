<?php

declare(strict_types=1);

namespace App\Policies\Admin\Coupons;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;

class CouponPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('coupons.view');
    }

    public function view(AdminUser $user, Coupon $coupon): bool
    {
        return $user->can('coupons.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('coupons.create');
    }

    public function update(AdminUser $user, Coupon $coupon): bool
    {
        return $user->can('coupons.edit');
    }

    public function delete(AdminUser $user, Coupon $coupon): bool
    {
        return $user->can('coupons.delete');
    }
}
