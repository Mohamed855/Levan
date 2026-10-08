<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class DomainLookupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower((string) $this->route('domain')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant') !== null && $this->user('tenant')->can('domain.view');
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'اسم النطاق مطلوب.',
            'domain.regex' => 'صيغة اسم النطاق غير صحيحة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => 'اسم النطاق',
        ];
    }
}
