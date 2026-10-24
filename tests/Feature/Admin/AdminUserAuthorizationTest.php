<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_edit_a_super_admin_even_with_update_permission(): void
    {
        $actor = $this->adminWith('operator', ['admin_users.update']);
        $superAdmin = $this->adminWith('SuperAdmin', []);

        $this->actingAs($actor, 'admin')
            ->get(route('admin.users.edit', $superAdmin))
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $actor = $this->adminWith('operator', ['admin_users.update']);

        $this->actingAs($actor, 'admin')
            ->deleteJson(route('admin.users.destroy', $actor))
            ->assertForbidden();

        $this->assertDatabaseHas('admin_users', ['id' => $actor->id]);
    }

    private function adminWith(string $roleName, array $permissions): AdminUser
    {
        $admin = AdminUser::factory()->create();
        $role = Role::query()->create([
            'guard' => 'admin',
            'name' => $roleName,
            'display_name' => $roleName,
        ]);

        foreach ($permissions as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['guard' => 'admin', 'name' => $name],
                ['display_name' => $name],
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $admin->assignRole($role);

        return $admin;
    }
}
