<?php

declare(strict_types=1);

namespace App\Policies\Tenants\UserManagement\Team;

use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teams.manage');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->can('teams.manage') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('teams.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can('teams.manage') && tenancy()->initialized;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('teams.manage') && tenancy()->initialized;
    }
}
