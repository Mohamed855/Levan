<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class SuggestDomainNamesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain_name' => ['required', 'string', 'min:2', 'max:63', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i'],
            'extension' => ['nullable', 'string', 'regex:/^\.[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return ['domain_name.required' => 'اسم النطاق مطلوب.', 'extension.regex' => 'امتداد النطاق غير صالح.'];
    }

    public function attributes(): array
    {
        return ['domain_name' => 'اسم النطاق', 'extension' => 'الامتداد'];
    }
}
