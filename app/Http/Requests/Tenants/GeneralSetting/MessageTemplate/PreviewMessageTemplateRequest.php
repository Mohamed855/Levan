<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\GeneralSetting\MessageTemplate;

use Illuminate\Foundation\Http\FormRequest;

class PreviewMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('message_templates.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string'],
            'sample_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'محتوى القالب مطلوب.',
            'sample_data.array' => 'بيانات المعاينة غير صالحة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'محتوى القالب',
            'category' => 'التصنيف',
            'sample_data' => 'بيانات المعاينة',
        ];
    }
}
