<?php

namespace App\Http\Requests\Tenants\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class RateTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('tickets.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'stars' => ['required', 'integer', 'between:1,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'stars.required' => 'يرجى اختيار التقييم.',
            'stars.between' => 'يجب أن يكون التقييم بين نجمة واحدة وخمس نجوم.',
        ];
    }
}
