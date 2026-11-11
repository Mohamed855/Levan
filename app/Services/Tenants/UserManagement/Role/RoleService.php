<?php

namespace App\Services\Tenants\UserManagement\Role;

use App\Enums\Shared\ActivityStatus;
use App\Models\Tenants\UserMangment\User\User;
use App\Support\AccessLabels;
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

    public const PERMISSION_SECTIONS = [
        'dashboard',
        'subscriptions_management',
        'bills_management',
        'users_management',
        'business_center',
        'system_management',
        'archive_management',
        'issuing_entities_management',
        'assignments_management',
        'properties_management',
        'auctions_management',
        'tickets_management',
    ];

    public const BASELINE_PERMISSION = 'dashboard.view';

    /*
    |--------------------------------------------------------------------------
    | Role Creation
    |--------------------------------------------------------------------------
    | Handle role creation with permissions.
    */
    public function createRole(array $data): Role
    {
        $permissions = $this->getPermissionsFromData($data);
        if (empty($permissions)) {
            throw new \InvalidArgumentException('يجب اختيار صلاحية واحدة على الأقل.');
        }

        return DB::transaction(function () use ($data, $permissions) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'tenant',
            ]);

            $role->syncPermissions($permissions);

            $this->clearPermissionsCache();

            return $role;
        });
    }

    public static function isProtectedRole(Role $role): bool
    {
        return (int) $role->id === 1
            || in_array(strtolower((string) $role->name), ['admin', 'superadmin'], true);
    }

    public function canEditRole(Role $role): bool
    {
        return ! self::isProtectedRole($role);
    }

    /*
    |--------------------------------------------------------------------------
    | Role Update
    |--------------------------------------------------------------------------
    | Handle role update with permissions.
    */
    public function updateRole(Role $role, array $data): Role
    {
        if (self::isProtectedRole($role)) {
            throw new \LogicException('لا يمكن تعديل دور مدير النظام.');
        }

        $permissions = $this->getPermissionsFromData($data);
        if (empty($permissions)) {
            throw new \InvalidArgumentException('يجب اختيار صلاحية واحدة على الأقل.');
        }

        return DB::transaction(function () use ($role, $data, $permissions) {
            $role->update(['name' => $data['name']]);

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
        if (self::isProtectedRole($role)) {
            throw new \LogicException('لا يمكن حذف الدور الأساسي.');
        }

        if ($role->users()->exists()) {
            throw new \LogicException('لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين حاليين في النظام.');
        }

        return DB::transaction(function () use ($role) {
            $deleted = $role->delete();
            $this->clearPermissionsCache();

            return (bool) $deleted;
        });
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
            $permissions = Permission::query()
                ->where('guard_name', 'tenant')
                ->pluck('name')
                ->toArray();
        } else {
            $permissions = $data['permissions'] ?? [];
        }

        if (! empty($permissions) && ! in_array(self::BASELINE_PERMISSION, $permissions, true)) {
            $permissions[] = self::BASELINE_PERMISSION;
        }

        return array_values(array_unique($permissions));
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

        return $permissions->sortBy(function ($items, $section) {
            $index = array_search($section, self::PERMISSION_SECTIONS, true);

            return $index === false ? 999 : $index;
        });
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
            'roles' => Role::where('guard_name', 'tenant')->get()->map(fn ($role) => ['id' => $role->id, 'name' => $role->name, 'label' => AccessLabels::role($role->name)]),
            'assigned_role' => $user->roles->first()?->id,
            'assigned_status' => $user->status,
            'status_options' => SelectOptions::fromEnum(ActivityStatus::class),
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

    public function getRoleStatistics(): array
    {
        $roles = Role::query()->where('guard_name', 'tenant');

        return [
            'total' => (clone $roles)->count(),
            'assigned' => (clone $roles)->has('users')->count(),
            'unassigned' => (clone $roles)->doesntHave('users')->count(),
            'permissions' => Permission::query()->where('guard_name', 'tenant')->count(),
            'custom' => (clone $roles)->where('name', '!=', 'Admin')->where('id', '!=', 1)->count(),
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
        return (int) $role->id !== 1 && $role->name !== 'Admin' && ! $role->users()->exists();
    }
}
