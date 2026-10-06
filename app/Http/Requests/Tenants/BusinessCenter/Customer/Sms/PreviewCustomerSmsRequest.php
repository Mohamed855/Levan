<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Sms;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCustomerSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:message_templates,id'],
        ];
    }
}
