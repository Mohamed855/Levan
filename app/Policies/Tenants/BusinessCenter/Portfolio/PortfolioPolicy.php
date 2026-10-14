<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Portfolio;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\User\User;

class PortfolioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('portfolios.view');
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->can('portfolios.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('portfolios.create');
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->can('portfolios.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $user->can('portfolios.delete') && tenancy()->initialized;
    }

    public function restore(User $user, Portfolio $portfolio): bool
    {
        return $user->can('portfolios_archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Portfolio $portfolio): bool
    {
        return $user->can('portfolios_archive.force_delete') && tenancy()->initialized;
    }
}
