<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Property;
use App\Models\Tenant\User;

final class PropertyPolicy
{
    public function view(User $user, Property $property): bool { return $user->hasPermissionTo('properties.view'); }
    public function create(User $user): bool { return $user->hasPermissionTo('properties.create'); }
    public function update(User $user, Property $property): bool { return $user->hasPermissionTo('properties.edit'); }
    public function delete(User $user, Property $property): bool { return $user->hasPermissionTo('properties.edit'); }
}
