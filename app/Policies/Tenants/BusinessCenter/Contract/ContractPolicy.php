<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Contract;

use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\UserMangment\User\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contracts.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->can('contracts.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->can('contracts.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->can('contracts.delete') && tenancy()->initialized;
    }

    public function restore(User $user, Contract $contract): bool
    {
        return $user->can('contracts_archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Contract $contract): bool
    {
        return $user->can('contracts_archive.force_delete') && tenancy()->initialized;
    }
}
