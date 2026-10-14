<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Import;

use Illuminate\Foundation\Http\FormRequest;

class CustomerImportCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.create') ?? false;
    }

    public function rules(): array
    {
        return ['company_id' => ['required', 'integer', 'exists:companies,id']];
    }

    public function messages(): array
    {
        return ['company_id.required' => 'الشركة مطلوبة.', 'company_id.exists' => 'الشركة المحددة غير موجودة.'];
    }

    public function attributes(): array
    {
        return ['company_id' => 'الشركة'];
    }
}
