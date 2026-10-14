<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('tickets.reply') ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'نص الرد مطلوب',
            'message.min' => 'يجب أن يحتوي الرد على حرفين على الأقل',
            'attachments.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
            'attachments.*.mimes' => 'نوع الملف غير مدعوم',
        ];
    }
}
