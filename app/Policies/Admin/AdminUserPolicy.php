<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;

final class AdminUserPolicy
{
    public function view(AdminUser $actor, AdminUser $user): bool
    {
        return $actor->hasPermissionTo('admin_users.view');
    }

    public function create(AdminUser $actor): bool
    {
        return $actor->hasPermissionTo('admin_users.create');
    }

    public function update(AdminUser $actor, AdminUser $user): bool
    {
        return $actor->hasPermissionTo('admin_users.update')
            && (! $user->isSuperAdmin() || $actor->isSuperAdmin());
    }

    public function delete(AdminUser $actor, AdminUser $user): bool
    {
        return $actor->hasPermissionTo('admin_users.update')
            && ! $actor->is($user)
            && ! $user->isSuperAdmin();
    }
}
