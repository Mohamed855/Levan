<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CouponWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_active_coupon_with_a_normalized_code_and_discount_cap(): void
    {
        $admin = $this->adminWithPermission('coupons.create');

        $response = $this->actingAs($admin, 'admin')->post(route('admin.coupons.store'), [
            'name' => 'خصم الإطلاق',
            'code' => ' launch-25 ',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'max_discount' => 75,
            'usage_limit' => 10,
            'is_active' => true,
        ]);

        $coupon = Coupon::query()->firstOrFail();

        $response->assertRedirect(route('admin.coupons.edit', $coupon));
        $this->assertSame('LAUNCH-25', $coupon->code);
        $this->assertTrue($coupon->is_active);
        $this->assertSame('75.00', $coupon->max_discount);
    }

    public function test_admin_cannot_create_a_percentage_coupon_above_one_hundred_percent(): void
    {
        $admin = $this->adminWithPermission('coupons.create');

        $this->actingAs($admin, 'admin')->from(route('admin.coupons.create'))->post(route('admin.coupons.store'), [
            'name' => 'خصم غير صالح',
            'code' => 'INVALID-101',
            'discount_type' => 'percentage',
            'discount_value' => 100.01,
            'is_active' => true,
        ])->assertRedirect(route('admin.coupons.create'))
            ->assertSessionHasErrors('discount_value');

        $this->assertDatabaseCount('coupons', 0);
    }

    private function adminWithPermission(string $permissionName): AdminUser
    {
        $permission = Permission::query()->create([
            'guard' => 'admin',
            'name' => $permissionName,
            'display_name' => $permissionName,
        ]);
        $role = Role::query()->create([
            'guard' => 'admin',
            'name' => 'coupon-manager-'.$permissionName,
            'display_name' => 'مدير الكوبونات',
        ]);
        $role->permissions()->sync([$permission->id]);

        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }
}
