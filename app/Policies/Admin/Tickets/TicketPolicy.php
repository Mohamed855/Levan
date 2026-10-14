<?php

declare(strict_types=1);

namespace App\Policies\Admin\Tickets;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tickets\Ticket;

final class TicketPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(AdminUser $user, Ticket $ticket): bool
    {
        return $user->can('tickets.view');
    }

    public function reply(AdminUser $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reply');
    }

    public function updateStatus(AdminUser $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reply');
    }
}
