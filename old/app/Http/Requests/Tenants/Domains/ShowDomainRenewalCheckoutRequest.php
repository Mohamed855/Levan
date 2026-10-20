<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class ShowDomainRenewalCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null;
    }

    public function rules(): array
    {
        return [
            'domain_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain_id' => 'النطاق',
        ];
    }
}
