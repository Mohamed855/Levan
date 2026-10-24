<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\ControlDomain;

final class ControlDomainPolicy
{
    public function view(AdminUser $user, ControlDomain $domain): bool { return $user->hasPermissionTo('control_domains.view'); }
    public function create(AdminUser $user): bool { return $user->hasPermissionTo('control_domains.create'); }
    public function update(AdminUser $user, ControlDomain $domain): bool { return $user->hasPermissionTo('control_domains.update'); }
    public function delete(AdminUser $user, ControlDomain $domain): bool { return $user->hasPermissionTo('control_domains.update'); }
}
