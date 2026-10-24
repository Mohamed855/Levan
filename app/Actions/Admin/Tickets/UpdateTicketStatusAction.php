<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tickets;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateTicketStatusAction
{
    private const ALLOWED_TRANSITIONS = [
        'open' => ['in_progress', 'resolved', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved' => ['closed', 'reopened'],
        'reopened' => ['in_progress', 'resolved', 'closed'],
        'closed' => ['reopened'],
    ];

    public function execute(Ticket $ticket, string|TicketStatus $status): Ticket
    {
        $statusValue = $status instanceof TicketStatus ? $status->value : $status;

        return DB::transaction(function () use ($ticket, $statusValue): Ticket {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->getKey());
            $currentStatus = $lockedTicket->status instanceof TicketStatus
                ? $lockedTicket->status->value
                : (string) $lockedTicket->status;

            if ($statusValue !== $currentStatus
                && ! in_array($statusValue, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'تغيرت حالة التذكرة؛ أعد تحميل الصفحة قبل تنفيذ الانتقال المطلوب.',
                ]);
            }

            $lockedTicket->update(['status' => $statusValue]);

            return $lockedTicket->refresh();
        });
    }
}
