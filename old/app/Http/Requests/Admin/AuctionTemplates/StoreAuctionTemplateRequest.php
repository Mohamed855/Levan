<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Rules\TemplateSchemaRule;
use Illuminate\Validation\Rule;

class StoreAuctionTemplateRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('auction_templates.create') ?? false;
    }

    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('auction_templates', 'name')->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::in(AuctionTemplate::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'schema' => ['required', 'json', new TemplateSchemaRule($type)],
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
            'type.required' => 'يرجى اختيار نوع القالب.',
            'schema.required' => 'مخطط القالب مطلوب.',
        ];
    }
}
