<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTicketStatusRequest extends FormRequest
{
    public const ALLOWED_TRANSITIONS = [
        'open'        => ['in_progress', 'resolved', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved'    => ['closed', 'reopened'],
        'reopened'    => ['in_progress', 'resolved', 'closed'],
        'closed'      => ['reopened'],
    ];

    public function authorize(): bool
    {
        return $this->user('admin')?->can('tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(TicketStatus::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    /** @var Ticket|null $ticket */
                    $ticket = $this->route('ticket');
                    if (! $ticket) {
                        return;
                    }

                    $currentStatus = $ticket->status instanceof TicketStatus
                        ? $ticket->status->value
                        : (string) $ticket->status;

                    $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

                    if (! in_array($value, $allowed, true) && $value !== $currentStatus) {
                        $currentLabel = TicketStatus::tryFrom($currentStatus)?->label() ?? $currentStatus;
                        $targetLabel = TicketStatus::tryFrom($value)?->label() ?? $value;
                        $fail("لا يمكن الانتقال من حالة ({$currentLabel}) إلى حالة ({$targetLabel}).");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حقل الحالة مطلوب.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'حالة التذكرة',
        ];
    }
}
