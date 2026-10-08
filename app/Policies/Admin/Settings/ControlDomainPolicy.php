<?php

declare(strict_types=1);

namespace App\Policies\Admin\Settings;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\ControlDomain\ControlDomain;

class ControlDomainPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('domain_extension_prices.settings');
    }

    public function view(AdminUser $user, ControlDomain $domain): bool
    {
        return $user->can('domain_extension_prices.settings');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('domain_extension_prices.settings');
    }

    public function update(AdminUser $user, ControlDomain $domain): bool
    {
        return $user->can('domain_extension_prices.settings');
    }

    public function delete(AdminUser $user, ControlDomain $domain): bool
    {
        return $user->can('domain_extension_prices.settings');
    }
}
