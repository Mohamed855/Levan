<?php

declare(strict_types=1);

namespace App\Policies\Admin\ContentManagement;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\ContentManagement\Portfolio;

class ContentPortfolioPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('clients.view');
    }

    public function view(AdminUser $user, Portfolio $portfolio): bool
    {
        return $user->can('clients.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(AdminUser $user, Portfolio $portfolio): bool
    {
        return $user->can('clients.edit');
    }

    public function delete(AdminUser $user, Portfolio $portfolio): bool
    {
        return $user->can('clients.delete');
    }
}
