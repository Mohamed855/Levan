<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Models\Tenants\UserMangment\User\User;

class CustomerUpdateRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return tenancy()->initialized && $user->can('customer_update_requests.view');
    }

    public function view(User $user, CustomerUpdateRequest $request): bool
    {
        return $this->viewAny($user);
    }

    public function approve(User $user, CustomerUpdateRequest $request): bool
    {
        return tenancy()->initialized && $user->can('customer_update_requests.approve');
    }

    public function reject(User $user, CustomerUpdateRequest $request): bool
    {
        return tenancy()->initialized && $user->can('customer_update_requests.reject');
    }
}
