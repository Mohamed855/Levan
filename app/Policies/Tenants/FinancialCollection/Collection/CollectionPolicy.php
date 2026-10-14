<?php

declare(strict_types=1);

namespace App\Policies\Tenants\FinancialCollection\Collection;

use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\UserMangment\User\User;

class CollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(User $user, Collection $collection): bool
    {
        return $user->can('collections.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('collections.create');
    }

    public function update(User $user, Collection $collection): bool
    {
        return $user->can('collections.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $user->can('collections.delete') && tenancy()->initialized;
    }

    public function approve(User $user, Collection $collection): bool
    {
        return $user->can('collections.approve') && tenancy()->initialized;
    }

    public function reject(User $user, Collection $collection): bool
    {
        return $user->can('collections.reject') && tenancy()->initialized;
    }

    public function restore(User $user, Collection $collection): bool
    {
        return $user->can('archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Collection $collection): bool
    {
        return $user->can('archive.force_delete') && tenancy()->initialized;
    }
}
