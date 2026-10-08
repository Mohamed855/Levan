<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return tenancy()->initialized
            && ($user->can('customers.view') || $customer->relationship_manager_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return tenancy()->initialized
            && ($user->can('customers.edit_all')
                || ($user->can('customers.edit') && $customer->relationship_manager_id === $user->id));
    }

    public function delete(User $user, Customer $customer): bool
    {
        return tenancy()->initialized
            && ($user->can('customers.delete_all')
                || ($user->can('customers.delete') && $customer->relationship_manager_id === $user->id));
    }
}
