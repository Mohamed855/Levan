<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Auctions;

use App\Rules\PublishedTemplateVersionRule;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

final class StoreAuctionRequest extends WizardFormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('auctions.create') ?? false; }

    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'template_versions' => ['nullable', 'array'],
            'template_versions.brochure' => ['nullable', 'integer', new PublishedTemplateVersionRule('brochure')],
            'template_versions.billboard' => ['nullable', 'integer', new PublishedTemplateVersionRule('billboard')],
            'template_versions.social' => ['nullable', 'integer', new PublishedTemplateVersionRule('social')],
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    protected function baseRules(): array
    {
        return [
            'assignment_id' => ['required', 'integer', Rule::exists('assignments', 'id')->whereNull('deleted_at')],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', 'distinct'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'end_time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(['electronic', 'in_person', 'hybrid'])],
            'category' => ['required', Rule::in(['residential', 'commercial', 'investment', 'educational', 'lands', 'mixed_use'])],
            'city' => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['required', 'integer', 'min:1'],
            'supervising_entity' => ['nullable', 'string', 'max:255'],
            'platform_name' => ['required', 'string', 'max:255'],
            'platform_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'status' => ['nullable', Rule::in(['in_preparation', 'pending_approval', 'active', 'completed', 'suspended'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment_id' => 'السند', 'name' => 'اسم المزاد', 'description' => 'وصف المزاد',
            'start_date' => 'تاريخ البداية', 'start_time' => 'وقت البداية', 'end_date' => 'تاريخ النهاية',
            'end_time' => 'وقت النهاية', 'type' => 'نوع المزاد', 'category' => 'تصنيف المزاد', 'city' => 'المدينة',
            'expected_properties_count' => 'عدد العقارات المتوقع', 'platform_name' => 'اسم المنصة',
            'platform_url' => 'رابط المنصة', 'cover_image' => 'صورة الغلاف',
        ];
    }
}
