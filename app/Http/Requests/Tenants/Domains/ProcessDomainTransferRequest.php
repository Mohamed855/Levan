<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class ProcessDomainTransferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower(trim((string) $this->input('domain'))),
            'epp_code' => trim((string) $this->input('epp_code', $this->input('eppCode'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.transfer') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/'],
            'epp_code' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'اسم النطاق مطلوب.',
            'domain.regex' => 'صيغة اسم النطاق غير صحيحة.',
            'epp_code.required' => 'كود EPP مطلوب.',
            'epp_code.min' => 'كود EPP يجب أن يكون 6 أحرف على الأقل.',
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
