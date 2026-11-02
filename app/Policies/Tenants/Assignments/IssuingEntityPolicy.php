<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Assignments;

use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\UserMangment\User\User;

class IssuingEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('issuing_entities.view');
    }

    public function view(User $user, IssuingEntity $issuingEntity): bool
    {
        return $user->can('issuing_entities.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('issuing_entities.create');
    }

    public function update(User $user, IssuingEntity $issuingEntity): bool
    {
        return $user->can('issuing_entities.edit') && tenancy()->initialized;
    }

    public function delete(User $user, IssuingEntity $issuingEntity): bool
    {
        return $user->can('issuing_entities.delete') && tenancy()->initialized;
    }
}
