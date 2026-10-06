<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCustomerSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.send_message') ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:message_templates,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ];
    }
}
