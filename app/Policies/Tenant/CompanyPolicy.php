<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Company;
use App\Models\Tenant\User;

final class CompanyPolicy
{
    public function view(User $user, Company $company): bool { return $user->hasPermissionTo('companies.view'); }
    public function create(User $user): bool { return $user->hasPermissionTo('companies.create'); }
    public function update(User $user, Company $company): bool { return $user->hasPermissionTo('companies.edit'); }
    public function delete(User $user, Company $company): bool { return $user->hasPermissionTo('companies.delete'); }
}
