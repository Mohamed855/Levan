<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Tickets;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyTenantTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('tickets.reply') ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'نص الرد',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'يرجى كتابة نص الرد.',
            'message.min' => 'يجب أن يحتوي الرد على حرفين على الأقل.',
        ];
    }
}
