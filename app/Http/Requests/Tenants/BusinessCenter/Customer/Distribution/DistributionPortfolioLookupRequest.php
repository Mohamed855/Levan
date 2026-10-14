<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution;

use Illuminate\Foundation\Http\FormRequest;

class DistributionPortfolioLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_distributions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'get_total' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['company_id.required' => 'الشركة مطلوبة.', 'company_id.exists' => 'الشركة المحددة غير موجودة.'];
    }

    public function attributes(): array
    {
        return ['company_id' => 'الشركة', 'portfolio_id' => 'المحفظة'];
    }
}
