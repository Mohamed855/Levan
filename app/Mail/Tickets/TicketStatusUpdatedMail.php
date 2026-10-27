<?php

declare(strict_types=1);

namespace App\Mail\Tickets;

use App\Enums\Tenants\Tickets\TicketStatus;
use App\Mail\BaseMailable;
use App\Models\Admin\Tickets\Ticket;

final class TicketStatusUpdatedMail extends BaseMailable
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $newStatus,
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

        $newStatusLabel = TicketStatus::tryFrom($this->newStatus)?->label() ?? $this->newStatus;
        $name = $this->customerName ?? $this->ticket->tenant?->company_name ?? 'عزيزنا العميل';

        return $this->subject("تحديث حالة التذكرة #{$this->ticket->ticket_number} إلى ({$newStatusLabel}) — لوت Levan")
            ->viewWithOffice('emails.tickets.status_updated', [
                'ticket'         => $this->ticket,
                'customerName'   => $name,
                'newStatus'      => $this->newStatus,
                'newStatusLabel' => $newStatusLabel,
                'viewUrl'        => $viewUrl,
            ]);
    }
}
