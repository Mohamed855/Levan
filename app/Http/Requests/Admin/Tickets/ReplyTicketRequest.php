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

    protected function prepareForValidation(): void
    {
        if ($this->has('message') && is_string($this->input('message'))) {
            $trimmed = trim($this->input('message'));
            $this->merge(['message' => $trimmed === '' ? null : $trimmed]);
        }
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'required_without:attachments', 'string', 'min:2'],
            'attachments' => ['nullable', 'array', 'min:1'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required_without' => 'يجب كتابة رد أو إرفاق ملف واحد على الأقل',
            'message.min' => 'يجب أن يحتوي الرد على حرفين على الأقل',
            'attachments.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
            'attachments.*.mimes' => 'نوع الملف غير مدعوم',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'إضافة رد على التذكرة:',
            'attachments' => 'المرفقات',
            'attachments.*' => 'المرفقات',
        ];
    }
}
