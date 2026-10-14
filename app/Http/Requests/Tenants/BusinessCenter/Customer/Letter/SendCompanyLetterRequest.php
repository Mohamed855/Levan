<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Letter;

use Illuminate\Foundation\Http\FormRequest;

class SendCompanyLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('company_letters.send') ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'recipient_email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'يرجى اختيار قالب البريد.',
            'recipient_email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'recipient_email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
        ];
    }

    public function attributes(): array
    {
        return ['template_id' => 'قالب البريد', 'recipient_email' => 'البريد الإلكتروني'];
    }
}
