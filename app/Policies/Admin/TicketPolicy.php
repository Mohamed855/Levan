<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Ticket;

final class TicketPolicy
{
    public function view(AdminUser $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.view');
    }

    public function reply(AdminUser $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.reply');
    }

    public function update(AdminUser $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.update');
    }

    public function delete(AdminUser $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.delete');
    }
}
