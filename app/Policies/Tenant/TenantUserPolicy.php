<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\User;

final class TenantUserPolicy
{
    public function view(User $actor, User $user): bool
    {
        return $actor->hasPermissionTo('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('users.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->hasPermissionTo('users.update')
            && (! $this->isOwner($user) || $this->isOwner($actor));
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->hasPermissionTo('users.delete')
            && ! $actor->is($user)
            && ! $this->isOwner($user);
    }

    private function isOwner(User $user): bool
    {
        return $user->roles()->where('name', 'tenant_owner')->exists();
    }
}
