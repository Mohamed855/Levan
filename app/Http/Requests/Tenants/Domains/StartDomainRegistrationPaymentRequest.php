<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class StartDomainRegistrationPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null && ($this->user('tenant')?->can('domain.reserve') ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower(trim((string) $this->input('domain', $this->input('domainname')))),
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
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'اسم النطاق مطلوب.',
            'domain.regex' => 'اسم النطاق غير صالح.',
        ];
    }
}
