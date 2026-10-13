<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Communication\MessageLog;

use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\UserMangment\User\User;

class MessageIndividualLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('message_logs.view');
    }

    public function view(User $user, MessageIndividualLog $messageIndividualLog): bool
    {
        return $user->can('message_logs.show') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MessageIndividualLog $messageIndividualLog): bool
    {
        return false;
    }

    public function delete(User $user, MessageIndividualLog $messageIndividualLog): bool
    {
        return $user->can('message_logs.delete') && tenancy()->initialized;
    }

    public function export(User $user): bool
    {
        return $user->can('message_logs.export');
    }
}
