<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Task;

use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\UserMangment\User\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return tenancy()->initialized && $user->can('tasks.view');
    }

    public function viewOwnQueue(User $user): bool
    {
        return tenancy()->initialized && $user->can('my_tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->viewAny($user) || $this->viewOwnQueue($user);
    }
}
