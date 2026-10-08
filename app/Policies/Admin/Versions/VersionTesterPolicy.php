<?php

declare(strict_types=1);

namespace App\Policies\Admin\Versions;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Version\VersionTester;

class VersionTesterPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('version_testers.view');
    }

    public function view(AdminUser $user, VersionTester $versionTester): bool
    {
        return $user->can('version_testers.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('version_testers.create');
    }

    public function update(AdminUser $user, VersionTester $versionTester): bool
    {
        return $user->can('version_testers.edit');
    }

    public function delete(AdminUser $user, VersionTester $versionTester): bool
    {
        return $user->can('version_testers.delete');
    }
}
