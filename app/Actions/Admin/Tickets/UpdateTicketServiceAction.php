<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tickets;

use App\Models\Admin\Ticket;
use Illuminate\Support\Facades\DB;

final class UpdateTicketServiceAction
{
    public function execute(Ticket $ticket, bool $serviceEnabled): Ticket
    {
        return DB::transaction(function () use ($ticket, $serviceEnabled): Ticket {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->getKey());
            $lockedTicket->update(['service_enabled' => $serviceEnabled]);

            return $lockedTicket->refresh();
        });
    }
}
