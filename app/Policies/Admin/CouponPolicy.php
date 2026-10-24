<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Coupon;

final class CouponPolicy
{
    public function view(AdminUser $user, Coupon $coupon): bool { return $user->hasPermissionTo('coupons.view'); }
    public function create(AdminUser $user): bool { return $user->hasPermissionTo('coupons.create'); }
    public function update(AdminUser $user, Coupon $coupon): bool { return $user->hasPermissionTo('coupons.update'); }
    public function delete(AdminUser $user, Coupon $coupon): bool { return $user->hasPermissionTo('coupons.delete'); }
}
