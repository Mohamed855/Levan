<?php

declare(strict_types=1);

namespace App\Policies\Admin\Plans;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;

class PlanPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('plans.view');
    }

    public function view(AdminUser $user, Plan $plan): bool
    {
        return $user->can('plans.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('plans.create');
    }

    public function update(AdminUser $user, Plan $plan): bool
    {
        return $user->can('plans.edit');
    }

    public function delete(AdminUser $user, Plan $plan): bool
    {
        return $user->can('plans.delete');
    }

    public function restore(AdminUser $user, Plan $plan): bool
    {
        return $user->can('plans.restore');
    }

    public function forceDelete(AdminUser $user, Plan $plan): bool
    {
        return $user->can('plans.force_delete');
    }
}
