<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Users\SaveAdminUserAction;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AdminUserManagementGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_assign_a_system_role(): void
    {
        $managerRole = $this->role('manager');
        $superAdminRole = $this->role('SuperAdmin');
        $actor = AdminUser::factory()->create();
        $actor->assignRole($managerRole);

        $this->expectException(AuthorizationException::class);

        app(SaveAdminUserAction::class)->create($this->data([$superAdminRole->id]), $actor);
    }

    public function test_last_active_super_admin_cannot_be_deactivated_or_have_the_role_removed(): void
    {
        $superAdminRole = $this->role('SuperAdmin');
        $regularRole = $this->role('operator');
        $superAdmin = AdminUser::factory()->create(['status' => AdminUserStatus::ACTIVE->value]);
        $superAdmin->assignRole($superAdminRole);

        $this->expectException(ValidationException::class);

        app(SaveAdminUserAction::class)->update($superAdmin, array_merge($this->data([$regularRole->id]), ['status' => AdminUserStatus::INACTIVE->value]), $superAdmin);
    }

    public function test_admin_cannot_deactivate_own_account_through_the_edit_workflow(): void
    {
        $role = $this->role('operator');
        $admin = AdminUser::factory()->create(['status' => AdminUserStatus::ACTIVE->value]);
        $admin->assignRole($role);

        $this->expectException(ValidationException::class);

        app(SaveAdminUserAction::class)->update($admin, array_merge($this->data([$role->id]), ['status' => AdminUserStatus::INACTIVE->value]), $admin);
    }

    private function role(string $name): Role
    {
        return Role::query()->create(['guard' => 'admin', 'name' => $name, 'display_name' => $name]);
    }

    /** @param list<int> $roleIds */
    private function data(array $roleIds): array
    {
        return ['name' => 'مدير اختبار', 'email' => fake()->unique()->safeEmail(), 'phone' => '0500000000', 'password' => 'password123', 'status' => AdminUserStatus::ACTIVE->value, 'role_ids' => $roleIds];
    }
}
