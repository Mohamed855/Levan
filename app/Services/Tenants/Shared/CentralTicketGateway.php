<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;
use App\Models\Admin\TicketAttachment;
use App\Models\Admin\TicketReply;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;
use Throwable;

final class CentralTicketGateway
{
    public function ticketsQueryForCurrentTenant(): Builder
    {
        return $this->centralTicketQuery()->where('tenant_id', $this->currentTenantId());
    }

    public function ticketForCurrentTenant(int|string $id): Ticket
    {
        return $this->ticketsQueryForCurrentTenant()
            ->with(['replies.adminUser', 'tenant', 'attachments'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('ticket_number', (string) $id);
            })
            ->firstOrFail();
    }

    /** @param array<string, mixed> $data @param array<int, UploadedFile> $attachments */
    public function createTicket(array $data, User $tenantUser, array $attachments = []): Ticket
    {
        $tenantId = $this->currentTenantId();
        $connection = $this->centralConnectionName();
        $storedAttachments = $this->storeAttachments($tenantId, $attachments);

        try {
            return DB::connection($connection)->transaction(function () use ($data, $tenantId, $tenantUser, $storedAttachments, $connection): Ticket {
                $ticket = (new Ticket())->setConnection($connection);
                $ticket->fill([
                    'ticket_number' => 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                    'tenant_id' => $tenantId,
                    'subject' => $data['subject'],
                    'description' => $data['description'],
                    'department' => $data['department'] ?? 'technical',
                    'status' => TicketStatus::OPEN,
                    'service_enabled' => true,
                ]);
                $ticket->save();

                foreach ($storedAttachments as $attachment) {
                    (new TicketAttachment())->setConnection($connection)->newQuery()->create([
                        'ticket_id' => $ticket->getKey(),
                        'tenant_user_id' => $tenantUser->getKey(),
                        'tenant_user_name' => $tenantUser->name,
                        ...$attachment,
                    ]);
                }

                return $ticket->load('attachments');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_column($storedAttachments, 'path'));

            throw $exception;
        }
    }

    public function replyTicket(Ticket $ticket, string $message, User $tenantUser): TicketReply
    {
        $tenantId = $this->currentTenantId();
        $connection = $this->centralConnectionName();

        return DB::connection($connection)->transaction(function () use ($ticket, $message, $tenantId, $tenantUser): TicketReply {
            $ownedTicket = $this->centralTicketQuery()
                ->whereKey($ticket->getKey())
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $ownedTicket->service_enabled) {
                throw ValidationException::withMessages([
                    'message' => 'خدمة الدعم لهذه التذكرة متوقفة حالياً. تواصل مع إدارة المنصة عند الحاجة.',
                ]);
            }

            $reply = (new TicketReply())->setConnection($this->centralConnectionName());
            $reply->fill([
                'ticket_id' => $ownedTicket->id,
                'admin_user_id' => null,
                'tenant_user_id' => $tenantUser->id,
                'tenant_user_name' => $tenantUser->name,
                'message' => $message,
            ]);
            $reply->save();

            if ($ownedTicket->status === TicketStatus::RESOLVED || $ownedTicket->status === TicketStatus::CLOSED) {
                $ownedTicket->update(['status' => TicketStatus::REOPENED]);
            }

            return $reply;
        });
    }

    public function statsForCurrentTenant(): array
    {
        $base = $this->ticketsQueryForCurrentTenant();

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', TicketStatus::OPEN->value)->count(),
            'in_progress' => (clone $base)->where('status', TicketStatus::IN_PROGRESS->value)->count(),
            'resolved' => (clone $base)->where('status', TicketStatus::RESOLVED->value)->count(),
            'closed' => (clone $base)->where('status', TicketStatus::CLOSED->value)->count(),
        ];
    }

    public function attachmentForCurrentTenant(Ticket $ticket, int|string $id): TicketAttachment
    {
        $tenantId = $this->currentTenantId();
        if ((string) $ticket->tenant_id !== $tenantId) {
            abort(404);
        }

        $attachment = $this->centralAttachmentQuery()->where('ticket_id', $ticket->getKey())->findOrFail($id);
        if (! str_starts_with($attachment->path, 'ticket-attachments/'.$tenantId.'/')) {
            abort(404);
        }

        return $attachment;
    }

    private function centralTicketQuery(): Builder
    {
        return (new Ticket())->setConnection($this->centralConnectionName())->newQuery();
    }

    private function centralAttachmentQuery(): Builder
    {
        return (new TicketAttachment())->setConnection($this->centralConnectionName())->newQuery();
    }

    /** @param array<int, UploadedFile> $attachments @return array<int, array{path:string,original_name:string,mime_type:string,size:int}> */
    private function storeAttachments(string $tenantId, array $attachments): array
    {
        $stored = [];

        try {
            foreach ($attachments as $attachment) {
                $path = $attachment->store('ticket-attachments/'.$tenantId, 'local');
                $stored[] = [
                    'path' => $path,
                    'original_name' => Str::limit(basename($attachment->getClientOriginalName()), 255, ''),
                    'mime_type' => (string) ($attachment->getMimeType() ?: 'application/octet-stream'),
                    'size' => (int) $attachment->getSize(),
                ];
            }
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_column($stored, 'path'));

            throw $exception;
        }

        return $stored;
    }

    private function centralConnectionName(): string
    {
        $connection = (string) config('tenancy.database.central_connection');

        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }

        return $connection;
    }

    private function currentTenantId(): string
    {
        $tenantId = tenant('id');

        if ($tenantId === null || $tenantId === '') {
            throw new TenancyNotInitializedException();
        }

        return (string) $tenantId;
    }
}
