<?php

declare(strict_types=1);

namespace App\Policies\Admin\Versions;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Version\Version;

class VersionPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('versions.view');
    }

    public function view(AdminUser $user, Version $version): bool
    {
        return $user->can('versions.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('versions.create');
    }

    public function update(AdminUser $user, Version $version): bool
    {
        return $user->can('versions.edit');
    }

    public function delete(AdminUser $user, Version $version): bool
    {
        return $user->can('versions.delete');
    }
}
