<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tickets;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;

final class UpdateTicketStatusAction
{
    public function execute(Ticket $ticket, string|TicketStatus $status): bool
    {
        $statusValue = $status instanceof TicketStatus ? $status->value : $status;
        return $ticket->update(['status' => $statusValue]);
    }
}
