<?php

declare(strict_types=1);

namespace App\Policies\Admin\Tenants;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;

class TenantPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('companies.view');
    }

    public function view(AdminUser $user, Tenant $tenant): bool
    {
        return $user->can('companies.show');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('companies.create');
    }

    public function update(AdminUser $user, Tenant $tenant): bool
    {
        return $user->can('companies.edit');
    }

    public function delete(AdminUser $user, Tenant $tenant): bool
    {
        return $user->can('companies.delete');
    }

    public function restore(AdminUser $user, Tenant $tenant): bool
    {
        return $user->can('companies.restore');
    }

    public function forceDelete(AdminUser $user, Tenant $tenant): bool
    {
        return $user->can('companies.force_delete');
    }
}
