<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;
use App\Models\Admin\TicketReply;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralTicketGateway
{
    public function ticketsQueryForCurrentTenant(): Builder
    {
        $tenantId = (string) tenant('id');

        return Ticket::query()->where('tenant_id', $tenantId);
    }

    public function ticketForCurrentTenant(int|string $id): Ticket
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): Ticket => Ticket::query()
            ->with(['replies.adminUser', 'tenant'])
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('ticket_number', (string) $id);
            })
            ->firstOrFail());
    }

    public function createTicket(array $data): Ticket
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($data, $tenantId): Ticket {
            return DB::transaction(function () use ($data, $tenantId) {
                $ticketNumber = 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                return Ticket::create([
                    'ticket_number' => $ticketNumber,
                    'tenant_id' => $tenantId,
                    'subject' => $data['subject'],
                    'description' => $data['description'],
                    'department' => $data['department'] ?? 'technical',
                    'status' => TicketStatus::OPEN,
                    'service_enabled' => true,
                ]);
            });
        });
    }

    public function replyTicket(Ticket $ticket, string $message): TicketReply
    {
        return Tenancy::central(function () use ($ticket, $message): TicketReply {
            return DB::transaction(function () use ($ticket, $message) {
                $reply = TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'admin_user_id' => null, // null denotes tenant author
                    'message' => $message,
                ]);

                if ($ticket->status === TicketStatus::RESOLVED || $ticket->status === TicketStatus::CLOSED) {
                    $ticket->update(['status' => TicketStatus::REOPENED]);
                }

                return $reply;
            });
        });
    }

    public function statsForCurrentTenant(): array
    {
        $tenantId = (string) tenant('id');
        $base = Ticket::query()->where('tenant_id', $tenantId);

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', TicketStatus::OPEN->value)->count(),
            'in_progress' => (clone $base)->where('status', TicketStatus::IN_PROGRESS->value)->count(),
            'resolved' => (clone $base)->where('status', TicketStatus::RESOLVED->value)->count(),
            'closed' => (clone $base)->where('status', TicketStatus::CLOSED->value)->count(),
        ];
    }
}
