<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('tickets.reply') ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'حقل الرد مطلوب.',
            'message.min'      => 'يجب أن يحتوي الرد على حرفين على الأقل.',
            'message.max'      => 'الحد الأقصى للرد هو 5000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'نص الرد',
        ];
    }
}
