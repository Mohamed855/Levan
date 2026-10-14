<?php

namespace App\Http\Requests\Tenants\Communication;

use Illuminate\Foundation\Http\FormRequest;

class FilterMessageLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('message_logs.view') ?? false;
    }

    /**
     */
    public function rules(): array
    {
        return [
            'sender_id' => ['nullable', 'integer', 'exists:users,id'],
            'message_type' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable'],
            'search.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له.'];
    }

    public function attributes(): array
    {
        return ['sender_id' => 'المرسل', 'message_type' => 'نوع الرسالة', 'platform' => 'المنصة', 'status' => 'الحالة', 'date_from' => 'من تاريخ', 'date_to' => 'إلى تاريخ'];
    }
}
