<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\ActivityLog;
use App\Models\Admin\AdminUser;

final class ActivityLogPolicy
{
    public function view(AdminUser $user, ActivityLog $activityLog): bool
    {
        return $user->hasPermissionTo('activity_log.view');
    }

    public function delete(AdminUser $user, ActivityLog $activityLog): bool
    {
        return $user->hasPermissionTo('activity_log.delete');
    }
}
