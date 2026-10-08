<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class DomainDnsLookupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower((string) $this->input('domain', $this->route('domain'))),
            'method' => strtolower((string) $this->input('method', $this->route('method'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
            'method' => ['required', 'in:nameserver,cname'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'اسم النطاق مطلوب.',
            'domain.regex' => 'صيغة اسم النطاق غير صحيحة.',
            'method.required' => 'طريقة التحقق مطلوبة.',
            'method.in' => 'طريقة التحقق غير مدعومة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => 'اسم النطاق',
            'method' => 'طريقة التحقق',
        ];
    }
}
