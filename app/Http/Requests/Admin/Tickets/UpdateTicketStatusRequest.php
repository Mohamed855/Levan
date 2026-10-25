<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use App\Enums\Tenants\Tickets\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('tickets.reply') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                TicketStatus::OPEN->value,
                TicketStatus::IN_PROGRESS->value,
                TicketStatus::RESOLVED->value,
                TicketStatus::RESOLVED_PENDING_SURVEY->value,
                TicketStatus::REOPENED->value,
                TicketStatus::CLOSED->value,
            ])],
        ];
    }
}
