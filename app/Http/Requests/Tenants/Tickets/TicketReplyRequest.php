<?php

namespace App\Http\Requests\Tenants\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class TicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'               => 'required|string|max:255',
            'attachments'           => 'nullable|array',
            'attachments.*'         => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required'                  => ' الرد مطلوب',
            'attachments.*.max'                 => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
            'attachments.*.mimes'               => 'نوع الملف غير مدعوم',
        ];
    }
}
