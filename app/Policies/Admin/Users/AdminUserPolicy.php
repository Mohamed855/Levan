<?php

declare(strict_types=1);

namespace App\Policies\Admin\Users;

use App\Models\Admin\AdminMangment\Users\AdminUser;

class AdminUserPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('users.view');
    }

    public function view(AdminUser $user, AdminUser $managedUser): bool
    {
        return $user->can('users.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('users.create');
    }

    public function update(AdminUser $user, AdminUser $managedUser): bool
    {
        return $user->can('users.edit');
    }

    public function delete(AdminUser $user, AdminUser $managedUser): bool
    {
        return $user->can('users.delete');
    }

    public function restore(AdminUser $user, AdminUser $managedUser): bool
    {
        return $user->can('users.restore');
    }

    public function forceDelete(AdminUser $user, AdminUser $managedUser): bool
    {
        return $user->can('users.force_delete');
    }
}
