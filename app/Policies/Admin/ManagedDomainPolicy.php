<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\ManagedDomain;
use App\Models\Tenant\User;

final class ManagedDomainPolicy
{
    public function viewAny(User $actor): bool { return $actor->hasPermissionTo('domains.view'); }
    public function view(User $actor, ManagedDomain $domain): bool { return $actor->hasPermissionTo('domains.view'); }
    public function create(User $actor): bool { return $actor->hasPermissionTo('domains.manage'); }
    public function update(User $actor, ManagedDomain $domain): bool { return $actor->hasPermissionTo('domains.manage'); }
    public function delete(User $actor, ManagedDomain $domain): bool { return $actor->hasPermissionTo('domains.manage'); }
}
