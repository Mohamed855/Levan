<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionCategory;
use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Enums\Tenants\Auctions\AuctionType;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Auctions\Auction;
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
            'project_manager_name' => ['required', 'string', 'max:255'],
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

            // Step 4: Results, marketing and files
            'property_results' => ['nullable', Rule::array($this->selectedPropertyKeys())],
            'property_results.*' => ['array'],
            'property_results.*.status' => ['required', Rule::in(['pending', 'sold', 'unsold'])],
            'property_results.*.opening_price' => ['nullable', 'numeric', 'min:0'],
            'property_results.*.sale_price' => ['nullable', 'required_if:property_results.*.status,sold', 'numeric', 'min:0'],
            'property_results.*.participants_count' => ['nullable', 'integer', 'min:0'],
            'property_results.*.bids_count' => ['nullable', 'integer', 'min:0'],
            'property_results.*.unsold_reason' => ['nullable', 'required_if:property_results.*.status,unsold', 'string', 'max:2000'],
            'property_results.*.board_location' => ['nullable', 'string', 'max:255'],
            'property_results.*.board_notes' => ['nullable', 'string', 'max:1000'],
            'marketing' => ['nullable', 'array'],
            'marketing.campaigns' => ['nullable', 'array', 'max:50'],
            'marketing.campaigns.*.platform' => ['required', 'string', 'max:100'],
            'marketing.campaigns.*.reach' => ['nullable', 'integer', 'min:0'],
            'marketing.campaigns.*.impressions' => ['nullable', 'integer', 'min:0'],
            'marketing.campaigns.*.engagements' => ['nullable', 'integer', 'min:0'],
            'marketing.campaigns.*.clicks' => ['nullable', 'integer', 'min:0'],
            'marketing.campaigns.*.url' => ['nullable', 'url', 'max:2048'],
            'marketing.links' => ['nullable', 'array', 'max:50'],
            'marketing.links.*.label' => ['required', 'string', 'max:255'],
            'marketing.links.*.url' => ['required', 'url', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'report_image' => [Rule::requiredIf(fn (): bool => ! $this->hasRetainedReportImage()), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx'],
            'marketing_evidence' => ['nullable', 'array'],
            'marketing_evidence.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,webp'],
            'board_images_before' => ['nullable', Rule::array($this->selectedPropertyKeys())],
            'board_images_before.*' => ['array'],
            'board_images_before.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'board_images_after' => ['nullable', Rule::array($this->selectedPropertyKeys())],
            'board_images_after.*' => ['array'],
            'board_images_after.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

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
            'project_manager_name.required' => 'اسم مدير المشروع مطلوب.',
            'report_image.required' => 'صورة التقرير مطلوبة للحفظ النهائي.',
            'report_image.image' => 'يجب أن تكون صورة التقرير ملف صورة صالحًا.',
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
            'project_manager_name' => 'مدير المشروع',
            'platform_name' => 'منصة المزاد',
            'platform_url' => 'رابط المزاد على المنصة',
            'property_ids' => 'العقارات المرتبطة',
            'property_results' => 'نتائج العقارات',
            'report_image' => 'صورة التقرير',
            'marketing' => 'بيانات التسويق',
        ];
    }

    /** @return list<string> */
    private function selectedPropertyKeys(): array
    {
        return array_map(static fn (mixed $id): string => (string) $id, (array) $this->input('property_ids', []));
    }

    private function hasRetainedReportImage(): bool
    {
        $auction = $this->route('auction');

        return $auction instanceof Auction && filled($auction->report_image);
    }
}
