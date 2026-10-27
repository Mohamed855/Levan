<?php

declare(strict_types=1);

namespace App\Mail\Tickets;

use App\Mail\BaseMailable;
use App\Models\Admin\Tickets\Ticket;

final class TicketCreatedClientMail extends BaseMailable
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?string $customerName = null
    ) {}

    public function build(): static
    {
        $tenantId = $this->ticket->tenant_id;
        $viewUrl = $tenantId 
            ? route('path.tickets.show', ['tenant' => $tenantId, 'ticket' => $this->ticket->ticket_number], false) 
            : config('app.url');

        if (! str_starts_with($viewUrl, 'http')) {
            $viewUrl = url($viewUrl);
        }

        $name = $this->customerName ?? $this->ticket->tenant?->company_name ?? 'عزيزنا العميل';

        return $this->subject("تم فتح تذكرة دعم فني جديدة #{$this->ticket->ticket_number} — لوت Levan")
            ->viewWithOffice('emails.tickets.created_client', [
                'ticket'       => $this->ticket,
                'customerName' => $name,
                'viewUrl'      => $viewUrl,
            ]);
    }
}
