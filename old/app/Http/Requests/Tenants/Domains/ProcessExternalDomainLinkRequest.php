<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class ProcessExternalDomainLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.connect') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain_name' => strtolower(trim((string) $this->input('domain_name'))),
            'ns1' => strtolower(rtrim(trim((string) $this->input('ns1')), '.')),
            'ns2' => strtolower(rtrim(trim((string) $this->input('ns2')), '.')),
        ]);
    }

    public function rules(): array
    {
        $hostnameRule = ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i'];

        return [
            'domain_name' => $hostnameRule,
            'ns1' => $hostnameRule,
            'ns2' => $hostnameRule,
        ];
    }

    public function messages(): array
    {
        return [
            'domain_name.required' => 'اسم الدومين مطلوب.',
            'domain_name.regex' => 'اسم الدومين غير صالح.',
            'ns1.required' => 'خادم الأسماء الأول مطلوب.',
            'ns1.regex' => 'خادم الأسماء الأول غير صالح.',
            'ns2.required' => 'خادم الأسماء الثاني مطلوب.',
            'ns2.regex' => 'خادم الأسماء الثاني غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domain_name' => 'اسم الدومين',
            'ns1' => 'خادم الأسماء الأول',
            'ns2' => 'خادم الأسماء الثاني',
        ];
    }
}
