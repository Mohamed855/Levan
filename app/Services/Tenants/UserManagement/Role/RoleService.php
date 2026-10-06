<?php

namespace App\Services\Tenants\UserManagement\Role;

use App\Models\Tenants\UserMangment\User\User;
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

    const PERMISSION_SECTIONS = [
        'لوحة القيادة',
        'مركز العمليات',
        'إدارة المهام',
        'إدارة التحصيل',
        'إدارة النظام',
        'التفارير والأرشيف',
    ];

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
                'guard_name' => 'tenant',
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
        if ($role->id == 1) {
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
        if (!empty($data['select_all'])) {
            return Permission::query()
                ->where('guard_name', 'tenant')
                ->pluck('name')
                ->toArray();
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
        $permissions = Permission::query()
            ->where('guard_name', 'tenant')
            ->get()
            ->groupBy('section');

        return $permissions;
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Role
    |--------------------------------------------------------------------------
    | Update user role and status.
    */
    public function updateUserRole(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $role = Role::query()
                ->where('guard_name', 'tenant')
                ->findOrFail($data['role']);

            $user->syncRoles([$role]);

            if (method_exists($user, 'additionalPermissions')) {
                $user->additionalPermissions()->detach();
            }
            if (method_exists($user, 'deniedPermissions')) {
                $user->deniedPermissions()->detach();
            }
            $user->status = $data['status'];
            $user->save();

            $this->clearPermissionsCache();

            return $user->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Role Data
    |--------------------------------------------------------------------------
    | Get user roles and status for editing.
    */
    public function getUserRoleData(User $user): array
    {
        return [
            'roles' => Role::where('guard_name', 'tenant')->get(),
            'assigned_role' => $user->roles->first()?->id,
            'assigned_status' => $user->status,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Status
    |--------------------------------------------------------------------------
    | Update user status only.
    */
    public function updateUserStatus(User $user, string $status): User
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
            ->where('guard_name', 'tenant')
            ->with('users')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Role Deletion
    |--------------------------------------------------------------------------
    | Check if role can be deleted.
    */
    public function canDeleteRole(Role $role): bool
    {
        return $role->id !== 1;
    }
}
