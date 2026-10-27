<?php

declare(strict_types=1);

namespace App\Mail\Tickets;

use App\Enums\Tenants\Tickets\TicketStatus;
use App\Mail\BaseMailable;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;

final class TicketReplyMail extends BaseMailable
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketReply $reply,
        public readonly string $recipientName,
        public readonly bool $isSupportReply = true
    ) {}

    public function build(): static
    {
        $tenantId = $this->ticket->tenant_id;
        if ($this->isSupportReply) {
            // Recipient is the client
            $viewUrl = $tenantId 
                ? route('path.tickets.show', ['tenant' => $tenantId, 'ticket' => $this->ticket->ticket_number], false) 
                : config('app.url');
        } else {
            // Recipient is support / admin
            $viewUrl = route('admin.tickets.show', $this->ticket->ticket_number, false);
        }

        if (! str_starts_with($viewUrl, 'http')) {
            $viewUrl = url($viewUrl);
        }

        $statusLabel = TicketStatus::tryFrom($this->ticket->status)?->label() ?? $this->ticket->status;

        return $this->subject("رد جديد على التذكرة #{$this->ticket->ticket_number} ({$this->ticket->title}) — لوت Levan")
            ->viewWithOffice('emails.tickets.reply', [
                'ticket'             => $this->ticket,
                'recipientName'      => $this->recipientName,
                'senderName'         => $this->reply->user_name ?? ($this->isSupportReply ? 'الدعم الفني' : 'العميل'),
                'replyMessage'       => $this->reply->message,
                'ticketStatusLabel'  => $statusLabel,
                'viewUrl'            => $viewUrl,
            ]);
    }
}
