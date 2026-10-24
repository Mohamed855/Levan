<?php

declare(strict_types=1);

namespace App\Policies\Admin\Tenants;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Tenants\Tenant;

final class TenantPolicy
{
    public function view(AdminUser $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->hasPermissionTo('tenants.create');
    }

    public function update(AdminUser $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.update');
    }

    public function delete(AdminUser $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('tenants.delete');
    }
}
