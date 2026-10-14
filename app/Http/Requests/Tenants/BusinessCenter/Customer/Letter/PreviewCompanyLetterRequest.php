<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Letter;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCompanyLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('company_letters.send') ?? false;
    }

    public function rules(): array
    {
        return ['template_id' => ['required', 'integer', 'exists:message_templates,id']];
    }

    public function messages(): array
    {
        return ['template_id.required' => 'يرجى اختيار قالب البريد.'];
    }

    public function attributes(): array
    {
        return ['template_id' => 'قالب البريد'];
    }
}
