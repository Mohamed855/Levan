<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class StartDomainRenewalPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null && ($this->user('tenant')?->can('domain.reserve') ?? false);
    }

    public function rules(): array
    {
        return [
            'domain_id' => ['required', 'integer', 'min:1'],
            'years' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain_id' => 'النطاق',
            'years' => 'مدة التجديد',
        ];
    }
}
