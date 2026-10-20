<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Properties;

use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->can('properties.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('properties.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->can('properties.delete') && tenancy()->initialized;
    }

    public function restore(User $user, Property $property): bool
    {
        return $user->can('properties_archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $user->can('properties_archive.force_delete') && tenancy()->initialized;
    }
}
