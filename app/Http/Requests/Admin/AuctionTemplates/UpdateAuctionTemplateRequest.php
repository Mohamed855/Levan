<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Rules\AuctionTemplateFileSpecificationRule;
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

        return [
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('auction_templates', 'name')->ignore($templateId)->whereNull('deleted_at'),
            ],
            'type'        => ['required', Rule::enum(AuctionTemplateType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'file'        => [
                'nullable',
                'file',
                'mimes:pdf,png,jpg,jpeg,webp',
                'max:30720',
                new AuctionTemplateFileSpecificationRule($this->input('type')),
            ],
            'thumbnail'   => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'اسم القالب',
            'type'        => 'نوع القالب',
            'description' => 'الوصف',
            'file'        => 'ملف القالب (PDF أو صورة)',
            'thumbnail'   => 'صورة المعاينة',
            'is_active'   => 'الحالة',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم القالب.',
            'name.unique'   => 'اسم القالب مستخدم بالفعل.',
            'type.required' => 'يرجى اختيار نوع القالب.',
            'file.mimes'    => 'يجب أن يكون الملف بصيغة PDF أو صورة (PNG, JPG, WEBP).',
            'file.max'      => 'أقصى حجم للملف هو 30 ميجابايت.',
        ];
    }
}
