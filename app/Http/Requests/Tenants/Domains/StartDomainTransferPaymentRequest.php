<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class StartDomainTransferPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower(trim((string) $this->input('domain'))),
            'epp_code' => trim((string) $this->input('epp_code', $this->input('eppCode'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:253',
                'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',
            ],
            'epp_code' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => 'اسم النطاق',
            'epp_code' => 'كود EPP',
        ];
    }
}
