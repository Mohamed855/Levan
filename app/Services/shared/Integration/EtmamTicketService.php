<?php

namespace App\Services\shared\Integration;

use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Admin\Tickets\TicketSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EtmamTicketService
{
    protected ?EtmamConnector $connector = null;

    protected function getConnector(): EtmamConnector
    {
        if ($this->connector === null) {
            $this->connector = new EtmamConnector(tenant('id'));
        }

        return $this->connector;
    }

    /**
     * Build the only local ticket query allowed from a tenant request.
     *
     * Ticket data is stored centrally for the support team, so an unscoped
     * lookup by ticket number would otherwise disclose another tenant's data.
     */
    private function tenantTicketQuery(): Builder
    {
        $tenantId = tenant('id') ?? $this->getConnector()->getTenantId();

        return Ticket::query()->where('tenant_id', $tenantId ?? '__missing_tenant__');
    }

    /**
     * Checks if external Etmam API integration is configured
     */
    public function isEtmamConfigured(): bool
    {
        return $this->getConnector()->isConfigured();
    }

    /**
     * Check if ticketing service is available.
     * Always returns true because local database fallback is fully supported.
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Check if creating new tickets is enabled by admin.
     */
    public function isServiceEnabled(): bool
    {
        return (bool) (Settings::on('mysql')->value('tickets_enabled') ?? true);
    }

    /**
     * List tickets with pagination & filters
     */
    public function list(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $query = array_merge(
                    $filters,
                    [
                        'tenant_id' => $this->getConnector()->getTenantId(),
                        'tenant_user_id' => auth()->id(),
                        'page' => $page,
                        'per_page' => $perPage,
                    ]
                );

                $response = $this->getConnector()->get('tenants/tickets', $query);
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API list failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $query = $this->tenantTicketQuery();

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['ticketing_sections_id'])) {
                $query->where('ticketing_sections_id', $filters['ticketing_sections_id']);
            }

            $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

            $formatted = collect($tickets->items())->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'ticketing_sections_id' => $ticket->ticketing_sections_id,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'customer_name' => $ticket->customer_name,
                    'created_at' => $ticket->created_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            })->all();

            return [
                'success' => true,
                'data' => [
                    'data' => [
                        'data' => $formatted,
                        'current_page' => $tickets->currentPage(),
                        'last_page' => $tickets->lastPage(),
                        'total' => $tickets->total(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Local list tickets failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'تعذر جلب التذاكر',
                'data' => ['data' => ['data' => []]],
            ];
        }
    }

    /**
     * Create a new ticket
     */
    public function create(array $data, array $files = []): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                if (empty($files)) {
                    $response = $this->getConnector()->post('tickets', $data);
                } else {
                    $response = $this->getConnector()->postWithFiles('tickets', $data, $files);
                }

                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API create failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $tenantId = tenant('id') ?? $this->getConnector()->getTenantId();

            if (! $tenantId) {
                return [
                    'success' => false,
                    'message' => 'تعذر تحديد المستأجر الحالي.',
                ];
            }

            $user = auth()->user();

            $uploadedAttachments = [];
            foreach ($files as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $path = $file->store('tickets/attachments', 'public');
                    $uploadedAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'url' => $path,
                        'size' => $file->getSize(),
                    ];
                }
            }

            // Generate unique ticket number
            $ticketNumber = 'TCK-'.date('Ymd').'-'.strtoupper(Str::random(5));

            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'tenant_id' => $tenantId,
                'tenant_user_id' => $data['tenant_user_id'] ?? $user?->id,
                'customer_name' => $data['customer_name'] ?? $user?->name ?? 'مستأجر',
                'customer_email' => $data['customer_email'] ?? $user?->email,
                'customer_phone' => $data['customer_phone'] ?? $user?->phone,
                'ticketing_sections_id' => $data['ticketing_sections_id'] ?? 1,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'open',
                'priority' => $data['priority'] ?? 'medium',
                'attachments' => $uploadedAttachments,
            ]);

            return [
                'success' => true,
                'message' => 'تم إنشاء التذكرة بنجاح',
                'data' => [
                    'data' => $ticket->toArray(),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Local create ticket failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التذكرة محلياً: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get ticket details
     */
    public function get(string $ticketNumber): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $response = $this->getConnector()->get("tickets/{$ticketNumber}");
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API get ticket failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $ticket = $this->tenantTicketQuery()
                ->where('ticket_number', $ticketNumber)
                ->first();

            if (! $ticket) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على التذكرة',
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'data' => [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'description' => $ticket->description,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'tenant_id' => $ticket->tenant_id,
                        'ticketing_sections_id' => $ticket->ticketing_sections_id,
                        'customer_name' => $ticket->customer_name,
                        'customer_email' => $ticket->customer_email,
                        'customer_phone' => $ticket->customer_phone,
                        'attachments' => is_array($ticket->attachments) ? $ticket->attachments : (json_decode($ticket->attachments, true) ?? []),
                        'survey_result' => $ticket->survey_result,
                        'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                        'closed_at' => $ticket->closed_at?->toIso8601String(),
                        'created_at' => $ticket->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Local get ticket failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'تعذر جلب تفاصيل التذكرة',
            ];
        }
    }

    /**
     * Add reply to ticket
     */
    public function addReply(string $ticketNumber, string $message, array $files = [], string $senderType = 'tenant_user', ?string $senderName = null): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $data = ['message' => $message];
                if (empty($files)) {
                    $response = $this->getConnector()->post("tickets/{$ticketNumber}/replies", $data);
                } else {
                    $response = $this->getConnector()->postWithFiles("tickets/{$ticketNumber}/replies", $data, $files);
                }

                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API addReply failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $ticket = $this->tenantTicketQuery()
                ->where('ticket_number', $ticketNumber)
                ->first();

            if (! $ticket) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على التذكرة',
                ];
            }

            $user = auth()->user();
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
                'sender_type' => $senderType,
                'user_id' => $user?->id,
                'user_name' => $senderName ?? $user?->name ?? ($senderType === 'support_user' ? 'الدعم الفني' : 'المستأجر'),
                'message' => $message,
                'attachments' => $uploadedAttachments,
            ]);

            // Update ticket status if replied by support
            if ($senderType === 'support_user' && $ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }

            return [
                'success' => true,
                'message' => 'تم إضافة الرد بنجاح',
            ];
        } catch (\Throwable $e) {
            Log::error('Local addReply failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'تعذر إضافة الرد: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get replies for a ticket
     */
    public function getReplies(string $ticketNumber): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $response = $this->getConnector()->get("tickets/{$ticketNumber}/replies");
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API getReplies failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $ticket = $this->tenantTicketQuery()
                ->where('ticket_number', $ticketNumber)
                ->first();

            if (! $ticket) {
                return [
                    'success' => true,
                    'data' => ['data' => []],
                ];
            }

            $replies = TicketReply::where('ticket_id', $ticket->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'sender_type' => $reply->sender_type,
                        'support_user_name' => $reply->user_name,
                        'message' => $reply->message,
                        'attachments' => $reply->attachments ?? [],
                        'rating' => ['rating' => $reply->rating],
                        'created_at' => $reply->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    ];
                })
                ->all();

            return [
                'success' => true,
                'data' => [
                    'data' => $replies,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Local getReplies failed: '.$e->getMessage());

            return [
                'success' => true,
                'data' => ['data' => []],
            ];
        }
    }

    /**
     * Rate a support reply
     */
    public function rateReply(string $ticketNumber, int $replyId, int $rating): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $response = $this->getConnector()->post("tickets/{$ticketNumber}/replies/{$replyId}/rate", ['stars' => $rating]);
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API rateReply failed: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $ticket = $this->tenantTicketQuery()
                ->where('ticket_number', $ticketNumber)
                ->first();

            $reply = $ticket
                ? TicketReply::query()
                    ->where('id', $replyId)
                    ->where('ticket_id', $ticket->id)
                    ->first()
                : null;
            if ($reply) {
                $reply->update(['rating' => $rating]);

                return [
                    'success' => true,
                    'message' => 'تم تقييم الرد بنجاح',
                ];
            }

            return [
                'success' => false,
                'message' => 'تعذر العثور على الرد للتقييم',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر تسجيل التقييم حالياً',
            ];
        }
    }

    /**
     * Answer final survey
     */
    public function answerSurvey(string $ticketNumber, array $data): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $response = $this->getConnector()->post("tickets/{$ticketNumber}/survey", $data);
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API answerSurvey failed: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $ticket = $this->tenantTicketQuery()
                ->where('ticket_number', $ticketNumber)
                ->first();
            if ($ticket) {
                $status = ($data['solved'] ?? false) ? 'resolved' : 'reopened';
                $ticket->update([
                    'status' => $status,
                    'survey_result' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'تم تسجيل إجابتك بنجاح',
                ];
            }

            return [
                'success' => false,
                'message' => 'تعذر العثور على التذكرة',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر تسجيل الإجابة حالياً',
            ];
        }
    }

    /**
     * Get ticketing sections
     */
    public function getSections(): array
    {
        if ($this->isEtmamConfigured()) {
            try {
                $response = $this->getConnector()->get('ticket-sections');
                if ($response['success']) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Etmam external API getSections failed, falling back to local: '.$e->getMessage());
            }
        }

        // Local Database Mode
        try {
            $sections = TicketSection::where('is_active', true)->get()->map(function ($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'description' => $section->description,
                ];
            })->all();

            if (empty($sections)) {
                $sections = [
                    ['id' => 1, 'name' => 'الدعم الفني', 'description' => 'المشاكل الفنية والأخطاء'],
                    ['id' => 2, 'name' => 'الاشتراكات والفوترة', 'description' => 'الاستفسارات المالية وتجديد الاشتراكات'],
                    ['id' => 3, 'name' => 'الاستفسارات العامة', 'description' => 'الأسئلة العامة والمقترحات'],
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'data' => $sections,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => true,
                'data' => [
                    'data' => [
                        ['id' => 1, 'name' => 'الدعم الفني', 'description' => 'المشاكل الفنية والأخطاء'],
                        ['id' => 2, 'name' => 'الاشتراكات والفوترة', 'description' => 'الاستفسارات المالية وتجديد الاشتراكات'],
                        ['id' => 3, 'name' => 'الاستفسارات العامة', 'description' => 'الأسئلة العامة والمقترحات'],
                    ],
                ],
            ];
        }
    }
}
