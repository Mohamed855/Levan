<?php

declare(strict_types=1);

namespace App\Policies\Tenants\UserManagement\User;

use App\Models\Tenants\UserMangment\User\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $user->can('users.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->can('users.edit') && tenancy()->initialized;
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $user->can('users.delete') && tenancy()->initialized;
    }
}
