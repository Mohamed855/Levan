<?php

namespace App\Services\Admin\AdminMangment\Role;

use App\Enums\Shared\ActivityStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Support\SelectOptions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    /*
    |--------------------------------------------------------------------------
    | Permission Sections
    |--------------------------------------------------------------------------
    | Define the permission sections used in the system.
    */

    const ADMIN_PERMISSION_SECTIONS = [];

    /*
    |--------------------------------------------------------------------------
    | Role Creation
    |--------------------------------------------------------------------------
    | Handle role creation with permissions.
    */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'admin',
            ]);

            $permissions = $this->getPermissionsFromData($data);
            $role->syncPermissions($permissions);

            $this->clearPermissionsCache();

            return $role;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Role Update
    |--------------------------------------------------------------------------
    | Handle role update with permissions.
    */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data['name']]);

            $permissions = $this->getPermissionsFromData($data);
            $role->syncPermissions($permissions);

            $this->clearPermissionsCache();

            return $role->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Role Deletion
    |--------------------------------------------------------------------------
    | Handle role deletion with validation.
    */
    public function deleteRole(Role $role): bool
    {
        if ($role->name === 'SuperAdmin') {
            throw new \Exception('لا يمكن حذف هذا الدور');
        }

        return DB::transaction(function () use ($role) {
            $deleted = $role->delete();
            $this->clearPermissionsCache();

            return $deleted;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Role With Details
    |--------------------------------------------------------------------------
    | Get role with additional details for display.
    */
    public function getRoleWithDetails(Role $role): array
    {
        return [
            'role' => $role->load('permissions', 'users'),
            'permissions_count' => $role->permissions()->count(),
            'users_count' => $role->users()->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Permissions From Data
    |--------------------------------------------------------------------------
    | Extract permissions from request data.
    */
    private function getPermissionsFromData(array $data): array
    {
        if (! empty($data['select_all'])) {
            return Permission::where('guard_name', 'admin')->pluck('name')->toArray();
        }

        return $data['permissions'] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Grouped Permissions
    |--------------------------------------------------------------------------
    | Get permissions grouped by section.
    */
    public function getGroupedPermissions(): \Illuminate\Support\Collection
    {
        $permissions = Permission::query()->where('guard_name', 'admin')->get()->groupBy('section');

        foreach (self::ADMIN_PERMISSION_SECTIONS as $section) {
            if (! $permissions->has($section)) {
                $permissions->put($section, collect());
            }
        }

        return $permissions;
    }

    /*
    |--------------------------------------------------------------------------
    | Update AdminUser Role
    |--------------------------------------------------------------------------
    | Update user role and status.
    */
    public function updateUserRole(AdminUser $user, array $data): AdminUser
    {
        return DB::transaction(function () use ($user, $data) {
            $role = Role::query()->where('guard_name', 'admin')->findOrFail($data['role']);

            $user->syncRoles([$role]);

            if (method_exists($user, 'additionalPermissions')) {
                $user->additionalPermissions()->detach();
            }
            if (method_exists($user, 'deniedPermissions')) {
                $user->deniedPermissions()->detach();
            }

            // $user->status = $data['status'];
            $user->save();

            $this->clearPermissionsCache();

            return $user->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get AdminUser Role Data
    |--------------------------------------------------------------------------
    | Get user roles and status for editing.
    */
    public function getUserRoleData(AdminUser $user): array
    {
        return [
            'roles' => Role::where('guard_name', 'admin')->get(),
            'assigned_role' => $user->roles->first()?->id,
            'assigned_status' => $user->status,
            'status_options' => SelectOptions::fromEnum(ActivityStatus::class),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Update AdminUser Status
    |--------------------------------------------------------------------------
    | Update user status only.
    */
    public function updateUserStatus(AdminUser $user, string $status): AdminUser
    {
        $user->status = $status;
        $user->save();

        $this->clearPermissionsCache();

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Permissions Cache
    |--------------------------------------------------------------------------
    | Clear the permissions cache.
    */
    private function clearPermissionsCache(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Roles
    |--------------------------------------------------------------------------
    | Get all roles for listing.
    */
    public function getAllRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->with('users')
            ->get();
    }

    public function getRoleStatistics(): array
    {
        $roles = Role::query()->where('guard_name', 'admin');

        return [
            'total' => (clone $roles)->count(),
            'assigned' => (clone $roles)->has('users')->count(),
            'unassigned' => (clone $roles)->doesntHave('users')->count(),
            'permissions' => Permission::query()->where('guard_name', 'admin')->count(),
            'custom' => (clone $roles)->where('name', '!=', 'SuperAdmin')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Role Deletion
    |--------------------------------------------------------------------------
    | Check if role can be deleted.
    */
    public function canDeleteRole(Role $role): bool
    {
        return $role->name !== 'SuperAdmin';
    }
}
