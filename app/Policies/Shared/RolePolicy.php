<?php

declare(strict_types=1);

namespace App\Policies\Shared;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(AdminUser|User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(AdminUser|User $user, Role $role): bool
    {
        return $this->belongsToActorGuard($user, $role) && $user->can('roles.view');
    }

    public function create(AdminUser|User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(AdminUser|User $user, Role $role): bool
    {
        return $this->belongsToActorGuard($user, $role) && $user->can('roles.edit');
    }

    public function delete(AdminUser|User $user, Role $role): bool
    {
        return $this->belongsToActorGuard($user, $role) && $user->can('roles.delete');
    }

    private function belongsToActorGuard(AdminUser|User $user, Role $role): bool
    {
        $expectedGuard = $user instanceof AdminUser ? 'admin' : 'tenant';

        return $role->guard_name === $expectedGuard;
    }
}
