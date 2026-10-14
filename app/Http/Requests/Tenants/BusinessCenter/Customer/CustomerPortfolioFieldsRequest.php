<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPortfolioFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.view') ?? false;
    }

    public function rules(): array
    {
        return ['portfolio_id' => ['required', 'integer', 'exists:portfolios,id']];
    }

    public function messages(): array
    {
        return ['portfolio_id.required' => 'المحفظة مطلوبة.', 'portfolio_id.exists' => 'المحفظة المحددة غير موجودة.'];
    }

    public function attributes(): array
    {
        return ['portfolio_id' => 'المحفظة'];
    }
}
