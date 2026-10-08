<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Company;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\UserMangment\User\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('companies.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.delete') && tenancy()->initialized;
    }
}
