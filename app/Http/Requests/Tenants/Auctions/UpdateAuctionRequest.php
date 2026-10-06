<?php

namespace App\Http\Requests\Tenants\Auctions;

use App\Http\Requests\Concerns\WizardFormRequest;

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
            'name'                      => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string', 'max:500'],
            'start_date'                => ['required', 'date'],
            'start_time'                => ['nullable'],
            'end_date'                  => ['required', 'date', 'after_or_equal:start_date'],
            'end_time'                  => ['nullable'],
            'type'                      => ['required', 'string', 'in:electronic,in_person,hybrid'],
            'category'                  => ['required', 'string'],
            'city'                      => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['nullable', 'integer', 'min:1'],
            'cover_image'               => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            // Step 2: Assignment & Platform
            'assignment_id'             => ['required', 'exists:assignments,id'],
            'supervising_entity'        => ['nullable', 'string', 'max:255'],
            'platform_name'             => ['nullable', 'string', 'max:255'],
            'platform_url'              => ['nullable', 'url', 'max:255'],
            'notes'                     => ['nullable', 'string', 'max:500'],

            // Step 3: Linked Properties
            'property_ids'              => ['nullable', 'array'],
            'property_ids.*'            => ['exists:properties,id'],

            // Step 4: Marketing Templates & Attachments
            'brochure_template_id'      => ['nullable', 'string'],
            'billboard_template_id'     => ['nullable', 'string'],
            'social_template_id'        => ['nullable', 'string'],
            'attachments'               => ['nullable', 'array'],
            'attachments.*'             => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx'],

            // Step 5: Status
            'status'                    => ['nullable', 'string', 'in:draft,in_preparation,pending_approval,active,completed,suspended'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'اسم المزاد مطلوب.',
            'name.max'                  => 'اسم المزاد يجب ألا يتجاوز 255 حرف.',
            'description.max'           => 'وصف المزاد يجب ألا يتجاوز 500 حرف.',
            'start_date.required'       => 'تاريخ بداية المزاد مطلوب.',
            'end_date.required'         => 'تاريخ نهاية المزاد مطلوب.',
            'end_date.after_or_equal'   => 'تاريخ نهاية المزاد يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'type.required'             => 'نوع المزاد مطلوب.',
            'category.required'         => 'تصنيف المزاد مطلوب.',
            'city.required'             => 'المدينة الرئيسية مطلوبة.',
            'assignment_id.required'    => 'يجب ربط المزاد بإسناد مسجل.',
            'assignment_id.exists'      => 'الإسناد المختار غير موجود.',
            'platform_url.url'          => 'رابط المنصة يجب أن يكون رابطاً صالحاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                      => 'اسم المزاد',
            'description'               => 'وصف المزاد',
            'start_date'                => 'تاريخ البداية',
            'start_time'                => 'وقت البداية',
            'end_date'                  => 'تاريخ النهاية',
            'end_time'                  => 'وقت النهاية',
            'type'                      => 'نوع المزاد',
            'category'                  => 'تصنيف المزاد',
            'city'                      => 'المدينة الرئيسية',
            'expected_properties_count' => 'عدد العقارات المتوقع',
            'assignment_id'             => 'الإسناد المرتبط',
            'supervising_entity'        => 'الجهة المشرفة',
            'platform_name'             => 'منصة المزاد',
            'platform_url'              => 'رابط المزاد على المنصة',
            'property_ids'              => 'العقارات المرتبطة',
        ];
    }
}
