<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class ConnectDomainRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $domain = strtolower(trim((string) $this->input('domain')));
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $domain = preg_replace('#^www\.#i', '', $domain) ?? $domain;

        $this->merge(['domain' => trim($domain, '/')]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.connect') ?? false;
    }

    public function rules(): array
    {
        return ['domain' => ['required', 'string', 'max:253', 'regex:/^([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i']];
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
        return ['domain' => 'اسم النطاق'];
    }
}
