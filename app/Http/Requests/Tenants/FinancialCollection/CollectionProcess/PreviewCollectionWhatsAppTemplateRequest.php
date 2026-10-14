<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\CollectionProcess;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCollectionWhatsAppTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.send_message') ?? false;
    }

    public function rules(): array
    {
        return ['template_id' => ['required', 'integer', 'exists:message_templates,id']];
    }

    public function messages(): array
    {
        return ['template_id.required' => 'قالب الرسالة مطلوب.'];
    }

    public function attributes(): array
    {
        return ['template_id' => 'قالب الرسالة'];
    }
}
