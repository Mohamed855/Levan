<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;

class AdminActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('activity_log.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'min:1'],
            'log_name' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له.',
            'search.value.max' => 'عبارة البحث طويلة جداً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'المستخدم',
            'log_name' => 'نوع السجل',
            'date_from' => 'تاريخ البداية',
            'date_to' => 'تاريخ النهاية',
            'search.value' => 'عبارة البحث',
        ];
    }
}
