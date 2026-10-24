<?php

declare(strict_types=1);

namespace App\Policies\Access;

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Tenant\User as TenantUser;

final class RolePolicy
{
    private const ADMIN_SYSTEM_ROLES = ['SuperAdmin', 'manager'];
    private const TENANT_SYSTEM_ROLES = ['tenant_owner', 'tenant_manager', 'tenant_user'];

    public function view(AdminUser|TenantUser $user, Role $role): bool
    {
        return $role->guard === $this->guardFor($user) && $user->hasPermissionTo('roles.view');
    }

    public function create(AdminUser|TenantUser $user): bool
    {
        return $user->hasPermissionTo('roles.create');
    }

    public function update(AdminUser|TenantUser $user, Role $role): bool
    {
        return $role->guard === $this->guardFor($user)
            && ! in_array($role->name, $this->systemRolesFor($user), true)
            && $user->hasPermissionTo('roles.update');
    }

    public function delete(AdminUser|TenantUser $user, Role $role): bool
    {
        return $role->guard === $this->guardFor($user)
            && ! in_array($role->name, $this->systemRolesFor($user), true)
            && $user->hasPermissionTo('roles.delete');
    }

    private function guardFor(AdminUser|TenantUser $user): string
    {
        return $user instanceof AdminUser ? 'admin' : 'tenant';
    }

    /** @return list<string> */
    private function systemRolesFor(AdminUser|TenantUser $user): array
    {
        return $user instanceof AdminUser ? self::ADMIN_SYSTEM_ROLES : self::TENANT_SYSTEM_ROLES;
    }
}
