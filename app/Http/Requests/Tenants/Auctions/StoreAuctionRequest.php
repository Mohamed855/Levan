<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionCategory;
use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Enums\Tenants\Auctions\AuctionType;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Http\Requests\Concerns\AuctionReportEvidenceRules;
use App\Http\Requests\Concerns\AuctionReportDetailRules;
use App\Models\Tenants\Assignments\Assignment;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Rule;

class StoreAuctionRequest extends WizardFormRequest
{
    use AuctionReportEvidenceRules;
    use AuctionReportDetailRules;
    private ?Assignment $resolvedAssignment = null;
    private bool $assignmentResolved = false;

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('auctions.create') ?? false;
    }

    public function rules(): array
    {
        return $this->reportEvidenceRules() + $this->reportDetailRules() + [
            // Step 1: Basic Info
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $assignment = $this->resolveAssignment();
                    if ($assignment && $assignment->start_date && $value) {
                        $auctionStart = Carbon::parse((string) $value)->startOfDay();
                        $asgStart = $assignment->start_date->startOfDay();
                        if ($auctionStart->lt($asgStart)) {
                            $fail('تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.$assignment->start_date->format('Y-m-d').').');
                        }
                    }
                },
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $assignment = $this->resolveAssignment();
                    if ($assignment && $assignment->end_date && $value) {
                        $auctionEnd = Carbon::parse((string) $value)->startOfDay();
                        $asgEnd = $assignment->end_date->startOfDay();
                        if ($auctionEnd->gt($asgEnd)) {
                            $fail('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.$assignment->end_date->format('Y-m-d').').');
                        }
                    }
                },
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $startDate = $this->input('start_date');
                    $endDate = $this->input('end_date');
                    $startTime = $this->input('start_time');

                    if ($startDate && $endDate && $startDate === $endDate && $startTime && $value) {
                        try {
                            $start = Carbon::createFromFormat('H:i', (string) $startTime);
                            $end = Carbon::createFromFormat('H:i', (string) $value);
                            if ($end->lessThanOrEqualTo($start)) {
                                $fail('وقت نهاية المزاد يجب أن يكون بعد وقت البداية عندما يكون المزاد في نفس اليوم.');
                            }
                        } catch (\Throwable) {}
                    }
                },
            ],
            'type' => ['required', Rule::enum(AuctionType::class)],
            'category' => ['required', Rule::enum(AuctionCategory::class)],
            'city' => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['required', 'integer', 'min:1'],

            // Step 2: Assignment & Platform
            'assignment_id' => [
                'required',
                Rule::exists('assignments', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $assignment = $this->resolveAssignment();
                    if (! $assignment) {
                        return;
                    }

                    if (! $assignment->is_active) {
                        $fail('الإسناد المختار غير نشط ولا يمكن ربطه بالمزاد.');

                        return;
                    }

                    $today = now()->startOfDay();
                    if ($assignment->end_date && $assignment->end_date->startOfDay()->lt($today)) {
                        $fail('الإسناد المختار منتهي الصلاحية بتاريخ '.$assignment->end_date->format('Y-m-d').'.');

                        return;
                    }

                    $startDate = $this->input('start_date');
                    $endDate = $this->input('end_date');

                    if ($startDate && $assignment->start_date) {
                        $auctionStart = Carbon::parse((string) $startDate)->startOfDay();
                        $asgStart = $assignment->start_date->startOfDay();
                        if ($auctionStart->lt($asgStart)) {
                            $fail('تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.$assignment->start_date->format('Y-m-d').').');
                        }
                    }

                    if ($endDate && $assignment->end_date) {
                        $auctionEnd = Carbon::parse((string) $endDate)->startOfDay();
                        $asgEnd = $assignment->end_date->startOfDay();
                        if ($auctionEnd->gt($asgEnd)) {
                            $fail('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.$assignment->end_date->format('Y-m-d').').');
                        }
                    }
                },
            ],
            'supervising_entity' => ['nullable', 'string', 'max:255'],
            'project_manager_name' => ['required', 'string', 'max:255'],
            'platform_name' => ['required', 'string', 'max:255'],
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
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value) {
                        return;
                    }
                    $property = \App\Models\Tenants\Properties\Property::query()->find($value);
                    if (! $property) {
                        return;
                    }
                    if ($property->status === 'sold') {
                        $fail("العقار ({$property->name}) تم بيعه بالفعل في مزاد سابق ولا يمكن إدراجه في مزاد جديد.");
                        return;
                    }
                    $isSoldInPivot = \Illuminate\Support\Facades\DB::table('auction_properties')
                        ->where('property_id', $value)
                        ->where('result_status', 'sold')
                        ->exists();
                    if ($isSoldInPivot) {
                        $fail("العقار ({$property->name}) مسجل كمباع في مزاد سابق.");
                        return;
                    }
                    $isInActiveAuction = \Illuminate\Support\Facades\DB::table('auction_properties')
                        ->join('auctions', 'auctions.id', '=', 'auction_properties.auction_id')
                        ->where('auction_properties.property_id', $value)
                        ->whereIn('auctions.status', ['active', 'in_preparation', 'pending_approval'])
                        ->whereNull('auctions.deleted_at')
                        ->exists();
                    if ($isInActiveAuction) {
                        $fail("العقار ({$property->name}) مدرج حالياً في مزاد نشط أو قيد الإعداد.");
                    }
                },
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
            'report_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المزاد مطلوب.',
            'name.max' => 'اسم المزاد يجب ألا يتجاوز 255 حرف.',
            'description.max' => 'وصف المزاد يجب ألا يتجاوز 500 حرف.',
            'description.required' => 'وصف المزاد مطلوب.',
            'start_date.required' => 'تاريخ بداية المزاد مطلوب.',
            'start_time.required' => 'وقت بداية المزاد مطلوب.',
            'end_date.required' => 'تاريخ نهاية المزاد مطلوب.',
            'end_time.required' => 'وقت نهاية المزاد مطلوب.',
            'end_date.after_or_equal' => 'تاريخ نهاية المزاد يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'type.required' => 'نوع المزاد مطلوب.',
            'category.required' => 'تصنيف المزاد مطلوب.',
            'city.required' => 'المدينة الرئيسية مطلوبة.',
            'assignment_id.required' => 'يجب ربط المزاد بإسناد مسجل.',
            'expected_properties_count.required' => 'عدد العقارات المتوقع مطلوب.',
            'platform_name.required' => 'منصة المزاد مطلوبة.',
            'project_manager_name.required' => 'اسم مدير المشروع مطلوب.',
            'report_image.image' => 'يجب أن تكون صورة التقرير ملف صورة صالحًا.',
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

    public function setResolvedAssignment(?Assignment $assignment): void
    {
        $this->resolvedAssignment = $assignment;
        $this->assignmentResolved = true;
    }

    private function resolveAssignment(): ?Assignment
    {
        if (! $this->assignmentResolved) {
            $assignmentId = $this->input('assignment_id');
            $this->resolvedAssignment = $assignmentId
                ? Assignment::query()->whereKey($assignmentId)->whereNull('deleted_at')->first()
                : null;
            $this->assignmentResolved = true;
        }

        return $this->resolvedAssignment;
    }
}
