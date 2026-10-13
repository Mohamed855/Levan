<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Communication\MessageLog;

use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\UserMangment\User\User;

class MessageLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('message_logs.view');
    }

    public function view(User $user, MessageLog $messageLog): bool
    {
        return $user->can('message_logs.show') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MessageLog $messageLog): bool
    {
        return false;
    }

    public function delete(User $user, MessageLog $messageLog): bool
    {
        return $user->can('message_logs.delete') && tenancy()->initialized;
    }
}
