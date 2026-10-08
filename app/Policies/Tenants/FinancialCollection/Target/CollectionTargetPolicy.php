<?php

declare(strict_types=1);

namespace App\Policies\Tenants\FinancialCollection\Target;

use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\UserMangment\User\User;

class CollectionTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('collection_targets.view');
    }

    public function view(User $user, CollectionTarget $collectionTarget): bool
    {
        return $user->can('collection_targets.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('collection_targets.create');
    }

    public function update(User $user, CollectionTarget $collectionTarget): bool
    {
        return $user->can('collection_targets.edit') && tenancy()->initialized;
    }

    public function delete(User $user, CollectionTarget $collectionTarget): bool
    {
        return $user->can('collection_targets.delete') && tenancy()->initialized;
    }
}
