<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tickets;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Ticket;
use App\Models\Admin\TicketReply;
use Illuminate\Support\Facades\DB;

final class ReplyTicketAction
{
    public function execute(Ticket $ticket, AdminUser $adminUser, string $message): TicketReply
    {
        return DB::transaction(function () use ($ticket, $adminUser, $message) {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            $reply = TicketReply::create([
                'ticket_id'     => $ticket->id,
                'admin_user_id' => $adminUser->id,
                'message'       => $message,
            ]);

            if ($ticket->status === TicketStatus::OPEN || (string) $ticket->status === 'open') {
                $ticket->update(['status' => TicketStatus::IN_PROGRESS]);
            }

            return $reply;
        });
    }
}
