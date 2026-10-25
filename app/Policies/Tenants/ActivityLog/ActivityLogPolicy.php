<?php

declare(strict_types=1);

namespace App\Policies\Tenants\ActivityLog;

use App\Models\Tenants\UserMangment\User\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activity_log.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can('activity_log.view');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->can('activity_log.view');
    }
}
