<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class ClearAllDomainCacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null && $this->user('tenant')->can('domain.view');
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
