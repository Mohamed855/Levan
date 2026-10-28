<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('activity_log.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'log_name' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
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
        return ['user_id' => 'المستخدم', 'log_name' => 'نوع السجل', 'date_from' => 'من تاريخ', 'date_to' => 'إلى تاريخ'];
    }
}
