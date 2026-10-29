<?php

declare(strict_types=1);

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Permission\Models\Role;

// Only active Tenant administrations that expose the shared mass-delete action.
return [
    'Assignment' => ['class' => Assignment::class, 'permission' => 'assignments.delete'],
    'Property' => ['class' => Property::class, 'permission' => 'properties.delete'],
    'Auction' => ['class' => Auction::class, 'permission' => 'auctions.delete'],
    'User' => ['class' => User::class, 'permission' => 'users.delete'],
    'Role' => ['class' => Role::class, 'permission' => 'roles.delete'],
];
