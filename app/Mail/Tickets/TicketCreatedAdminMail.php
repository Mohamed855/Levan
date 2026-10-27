<?php

declare(strict_types=1);

namespace App\Mail\Tickets;

use App\Mail\BaseMailable;
use App\Models\Admin\Tickets\Ticket;

final class TicketCreatedAdminMail extends BaseMailable
{
    public function __construct(
        public readonly Ticket $ticket
    ) {}

    public function build(): static
    {
        $companyName = $this->ticket->tenant?->company_name ?? (string) $this->ticket->tenant_id;

        return $this->subject("تذكرة جديدة #{$this->ticket->ticket_number} من ({$companyName}) — لوت Levan")
            ->viewWithOffice('emails.tickets.created_admin', [
                'ticket'      => $this->ticket,
                'companyName' => $companyName,
            ]);
    }
}
