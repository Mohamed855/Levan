<?php

declare(strict_types=1);

namespace App\Policies\Admin\Clients;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Client;
use App\Models\Admin\Tenants\Tenant;

class ClientPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('clients.view') || $user->can('companies.view');
    }

    public function view(AdminUser $user, Tenant|Client $client): bool
    {
        return $user->can('clients.show') || $user->can('companies.show');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('clients.create') || $user->can('companies.create');
    }

    public function update(AdminUser $user, Tenant|Client $client): bool
    {
        return $user->can('clients.edit') || $user->can('companies.edit');
    }

    public function delete(AdminUser $user, Tenant|Client $client): bool
    {
        return $user->can('clients.delete') || $user->can('companies.delete');
    }

    public function restore(AdminUser $user, Tenant|Client $client): bool
    {
        return $user->can('clients.restore') || $user->can('companies.restore');
    }

    public function forceDelete(AdminUser $user, Tenant|Client $client): bool
    {
        return $user->can('clients.force_delete') || $user->can('companies.force_delete');
    }
}
