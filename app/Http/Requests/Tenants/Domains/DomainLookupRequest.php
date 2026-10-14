<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

final class DomainLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'اسم النطاق مطلوب.',
            'domain.regex' => 'اسم النطاق غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return ['domain' => 'اسم النطاق'];
    }
}
