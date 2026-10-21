<?php

namespace App\Http\Requests\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionCategory;
use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Enums\Tenants\Auctions\AuctionType;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Rules\ExistsInCentralAuctionTemplates;
use Illuminate\Validation\Rule;

class UpdateAuctionRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('auctions.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            // Step 1: Basic Info
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'end_time' => ['nullable'],
            'type' => ['required', Rule::enum(AuctionType::class)],
            'category' => ['required', Rule::enum(AuctionCategory::class)],
            'city' => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['nullable', 'integer', 'min:1'],

            // Step 2: Assignment & Platform
            'assignment_id' => ['required', Rule::exists('assignments', 'id')->whereNull('deleted_at')],
            'supervising_entity' => ['nullable', 'string', 'max:255'],
            'platform_name' => ['nullable', 'string', 'max:255'],
            'platform_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Step 3: Linked Properties
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => [
                'distinct',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query
                        ->where('assignment_id', $this->input('assignment_id'))
                        ->whereNull('deleted_at'),
                ),
            ],

            // Step 4: Marketing Templates & Attachments
            'template_versions' => ['nullable', 'array'],
            'template_versions.brochure' => ['nullable', 'integer', new \App\Rules\PublishedTemplateVersionRule('brochure')],
            'template_versions.billboard' => ['nullable', 'integer', new \App\Rules\PublishedTemplateVersionRule('billboard')],
            'template_versions.social' => ['nullable', 'integer', new \App\Rules\PublishedTemplateVersionRule('social')],
            'brochure_template_id' => ['nullable', 'integer', new ExistsInCentralAuctionTemplates('brochure')],
            'billboard_template_id' => ['nullable', 'integer', new ExistsInCentralAuctionTemplates('billboard')],
            'social_template_id' => ['nullable', 'integer', new ExistsInCentralAuctionTemplates('social')],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx'],

            // Step 5: Status
            'status' => ['nullable', Rule::enum(AuctionStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المزاد مطلوب.',
            'name.max' => 'اسم المزاد يجب ألا يتجاوز 255 حرف.',
            'description.max' => 'وصف المزاد يجب ألا يتجاوز 500 حرف.',
            'start_date.required' => 'تاريخ بداية المزاد مطلوب.',
            'end_date.required' => 'تاريخ نهاية المزاد مطلوب.',
            'end_date.after_or_equal' => 'تاريخ نهاية المزاد يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'type.required' => 'نوع المزاد مطلوب.',
            'category.required' => 'تصنيف المزاد مطلوب.',
            'city.required' => 'المدينة الرئيسية مطلوبة.',
            'assignment_id.required' => 'يجب ربط المزاد بإسناد مسجل.',
            'assignment_id.exists' => 'الإسناد المختار غير موجود.',
            'property_ids.*.exists' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار.',
            'property_ids.*.distinct' => 'لا يمكن اختيار العقار نفسه أكثر من مرة.',
            'platform_url.url' => 'رابط المنصة يجب أن يكون رابطاً صالحاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المزاد',
            'description' => 'وصف المزاد',
            'start_date' => 'تاريخ البداية',
            'start_time' => 'وقت البداية',
            'end_date' => 'تاريخ النهاية',
            'end_time' => 'وقت النهاية',
            'type' => 'نوع المزاد',
            'category' => 'تصنيف المزاد',
            'city' => 'المدينة الرئيسية',
            'expected_properties_count' => 'عدد العقارات المتوقع',
            'assignment_id' => 'الإسناد المرتبط',
            'supervising_entity' => 'الجهة المشرفة',
            'platform_name' => 'منصة المزاد',
            'platform_url' => 'رابط المزاد على المنصة',
            'property_ids' => 'العقارات المرتبطة',
        ];
    }
}
