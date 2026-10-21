<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Rules\TemplateSchemaRule;
use Illuminate\Validation\Rule;

class UpdateAuctionTemplateRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('auction_templates.edit') ?? false;
    }

    public function rules(): array
    {
        $template = $this->route('auction_template');
        $templateId = $template instanceof AuctionTemplate ? $template->id : $template;
        $type = $template instanceof AuctionTemplate
            ? (is_object($template->type) ? $template->type->value : (string) $template->type)
            : (string) $this->input('type', 'brochure');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('auction_templates', 'name')->ignore($templateId)->whereNull('deleted_at'),
            ],
            'type' => ['sometimes', Rule::in(AuctionTemplate::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'schema' => ['nullable', 'json', new TemplateSchemaRule($type)],
            'file' => ['nullable', 'file', 'max:30720'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم القالب',
            'type' => 'نوع القالب',
            'description' => 'الوصف',
            'schema' => 'مخطط القالب',
            'is_active' => 'الحالة',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم القالب.',
            'name.unique' => 'اسم القالب مستخدم بالفعل.',
        ];
    }
}
