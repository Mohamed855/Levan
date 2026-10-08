<?php

declare(strict_types=1);

namespace App\Policies\Admin\ContentManagement;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\ContentManagement\Client;

class ClientPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('clients.view');
    }

    public function view(AdminUser $user, Client $client): bool
    {
        return $user->can('clients.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(AdminUser $user, Client $client): bool
    {
        return $user->can('clients.edit');
    }

    public function delete(AdminUser $user, Client $client): bool
    {
        return $user->can('clients.delete');
    }
}
