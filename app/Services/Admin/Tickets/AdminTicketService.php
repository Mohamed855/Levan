<?php

namespace App\Services\Admin\Tickets;

use App\Enums\Tenants\Tickets\TicketStatus;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Admin\Tickets\TicketSection;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\shared\Integration\EtmamTicketService;
use App\Services\Shared\Notifications\DashboardNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AdminTicketService
{
    public function __construct(private EtmamTicketService $etmamService) {}

    /**
     * Get ticket statistics / KPIs
     */
    public function getStats(): array
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::whereIn('status', ['resolved', 'resolved_pending_survey'])->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
        ];
    }

    public function ticketsEnabled(): bool
    {
        return Settings::query()->value('tickets_enabled') ?? true;
    }

    public function updateTicketsEnabled(bool $enabled): bool
    {
        return DB::transaction(function () use ($enabled): bool {
            $settings = Settings::query()->firstOrFail();
            $settings->update(['tickets_enabled' => $enabled]);

            return $enabled;
        });
    }

    /**
     * Get all tickets query for DataTable
     */
    public function getTicketsQuery(array $filters = [])
    {
        $query = Ticket::with(['tenant', 'section'])->select('tickets.*');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['ticketing_sections_id'])) {
            $query->where('ticketing_sections_id', $filters['ticketing_sections_id']);
        }

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return $query;
    }

    /**
     * Get ticket by number
     */
    public function getTicket(string $ticketNumber): ?Ticket
    {
        return Ticket::with(['tenant', 'section', 'replies'])->where('ticket_number', $ticketNumber)->first();
    }

    /**
     * Add reply from Admin / Support
     */
    public function addReply(string $ticketNumber, string $message, array $files = []): array
    {
        $admin = auth('admin')->user();
        $senderName = $admin?->name ?? 'الدعم الفني';

        // Check if external Etmam is configured
        if ($this->etmamService->isEtmamConfigured()) {
            $response = $this->etmamService->addReply($ticketNumber, $message, $files, 'support_user', $senderName);

            return $response;
        }

        $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
        if (! $ticket) {
            return ['success' => false, 'message' => 'لم يتم العثور على التذكرة'];
        }

        $uploadedAttachments = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = $file->store('tickets/replies', 'public');
                $uploadedAttachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'url' => $path,
                ];
            }
        }

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticketNumber,
            'sender_type' => 'support_user',
            'user_id' => $admin?->id,
            'user_name' => $senderName,
            'message' => $message,
            'attachments' => $uploadedAttachments,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        // The committed TicketReply observer delivers the notification exactly once.

        return ['success' => true, 'message' => 'تم إرسال الرد بنجاح'];
    }

    /**
     * Update ticket status
     */
    public function updateStatus(string $ticketNumber, string $status): array
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
        if (! $ticket) {
            return ['success' => false, 'message' => 'لم يتم العثور على التذكرة'];
        }

        $ticket->update(['status' => $status]);

        $statusLabel = TicketStatus::tryFrom($status)?->label() ?? $status;

        // Notify tenant about status change
        $this->notifyTenant(
            $ticket,
            'تحديث حالة التذكرة #'.$ticketNumber,
            "تم تغيير حالة تذكرتك ({$ticket->title}) إلى: {$statusLabel}",
            'warning',
            'ti ti-refresh'
        );

        return ['success' => true, 'message' => 'تم تحديث حالة التذكرة بنجاح'];
    }

    /**
     * Send notification to tenant user regarding ticket update
     */
    private function notifyTenant(Ticket $ticket, string $title, string $message, string $type = 'info', string $icon = 'ti ti-ticket'): void
    {
        if (! $ticket->tenant_id) {
            return;
        }
        $notices = app(DashboardNotificationService::class);
        $notices->tenant($ticket->tenant_id, "ticket.status:$ticket->id:$ticket->status", $notices::payload('ticket.status', $title, $message,
            route('path.tickets.show', ['tenant' => $ticket->tenant_id, 'ticket' => $ticket->ticket_number], false), $type, $icon), $ticket->tenant_user_id);
    }

    /**
     * Get sections
     */
    public function getSections()
    {
        return TicketSection::where('is_active', true)->get();
    }
}
