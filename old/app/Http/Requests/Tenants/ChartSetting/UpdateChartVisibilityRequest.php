<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\ChartSetting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateChartVisibilityRequest extends FormRequest
{
    private const ALLOWED_KEYS = ['compare_effort', 'daily_tasks', 'Overdue_tasks'];

    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(self::ALLOWED_KEYS)],
            'value' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'يجب تحديد الرسم البياني.',
            'key.in' => 'الرسم البياني المحدد غير صالح.',
            'value.required' => 'يجب تحديد حالة العرض.',
            'value.boolean' => 'حالة العرض يجب أن تكون قيمة منطقية.',
        ];
    }

    public function attributes(): array
    {
        return ['key' => 'الرسم البياني', 'value' => 'حالة العرض'];
    }
}
