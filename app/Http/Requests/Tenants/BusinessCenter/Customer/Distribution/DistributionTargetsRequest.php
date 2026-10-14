<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution;

use Illuminate\Foundation\Http\FormRequest;

class DistributionTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_distributions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'distribution_result' => ['required', 'array'],
            'duration' => ['required', 'integer', 'min:1', 'max:12'],
            'start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_year' => ['required', 'integer', 'min:2024'],
        ];
    }

    public function messages(): array
    {
        return ['distribution_result.required' => 'نتيجة التوزيع مطلوبة.', 'duration.required' => 'مدة الأهداف مطلوبة.'];
    }

    public function attributes(): array
    {
        return ['distribution_result' => 'نتيجة التوزيع', 'duration' => 'المدة', 'start_month' => 'شهر البداية', 'start_year' => 'سنة البداية'];
    }
}
