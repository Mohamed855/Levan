<?php

declare(strict_types=1);

namespace App\Policies\Admin\Plans;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Plans\Plan;

final class PlanPolicy
{
    public function view(AdminUser $user, Plan $plan): bool { return $user->hasPermissionTo('plans.view'); }
    public function create(AdminUser $user): bool { return $user->hasPermissionTo('plans.create'); }
    public function update(AdminUser $user, Plan $plan): bool { return $user->hasPermissionTo('plans.update'); }
    public function delete(AdminUser $user, Plan $plan): bool { return $user->hasPermissionTo('plans.delete'); }
}
