<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auctions;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Auctions\AuctionCategory;
use App\Enums\Tenants\Auctions\AuctionType;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateAuctionRequest extends WizardFormRequest
{
    private ?Assignment $resolvedAssignment = null;

    private bool $assignmentResolved = false;

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('auctions.edit') ?? false;
    }

    public function rules(): array
    {
        $auction = $this->route('auction');
        $platformEntityRule = Rule::exists('issuing_entities', 'id');
        if (! $auction instanceof Auction || (int) $this->input('platform_entity_id') !== $auction->platform_entity_id) {
            $platformEntityRule->whereNull('deleted_at');
        }

        return [
            // Step 1: Basic Info
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $assignment = $this->resolveAssignment();
                    if ($assignment && $assignment->start_date && $value) {
                        $auctionStart = Carbon::parse((string) $value)->startOfDay();
                        $asgStart = $assignment->start_date->startOfDay();
                        if ($auctionStart->lt($asgStart)) {
                            $fail('تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.system_date($assignment->start_date).').');
                        }
                    }
                },
            ],
            'start_time' => ['nullable'],
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
                            $fail('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.system_date($assignment->end_date).').');
                        }
                    }
                },
            ],
            'end_time' => [
                'nullable',
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
                        } catch (\Throwable) {
                        }
                    }
                },
            ],
            'type' => ['required', Rule::enum(AuctionType::class)],
            'category' => ['required', Rule::enum(AuctionCategory::class)],
            'city' => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['nullable', 'integer', 'min:1'],

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

                    $auction = $this->route('auction');
                    $isSameAssignment = $auction instanceof Auction && (int) $auction->assignment_id === (int) $value;

                    if (! $isSameAssignment) {
                        if (! $assignment->is_active) {
                            $fail('الإسناد المختار غير نشط ولا يمكن ربطه بالمزاد.');

                            return;
                        }

                        $today = now()->startOfDay();
                        if ($assignment->end_date && $assignment->end_date->startOfDay()->lt($today)) {
                            $fail('الإسناد المختار منتهي الصلاحية بتاريخ '.system_date($assignment->end_date).'.');

                            return;
                        }
                    }

                    $startDate = $this->input('start_date');
                    $endDate = $this->input('end_date');

                    if ($startDate && $assignment->start_date) {
                        $auctionStart = Carbon::parse((string) $startDate)->startOfDay();
                        $asgStart = $assignment->start_date->startOfDay();
                        if ($auctionStart->lt($asgStart)) {
                            $fail('تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.system_date($assignment->start_date).').');
                        }
                    }

                    if ($endDate && $assignment->end_date) {
                        $auctionEnd = Carbon::parse((string) $endDate)->startOfDay();
                        $asgEnd = $assignment->end_date->startOfDay();
                        if ($auctionEnd->gt($asgEnd)) {
                            $fail('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.system_date($assignment->end_date).').');
                        }
                    }
                },
            ],
            'supervising_entity' => ['nullable', 'string', 'max:255'],
            'project_manager_name' => ['required', 'string', 'max:255'],
            'platform_entity_id' => ['required', 'integer', $platformEntityRule],
            'platform_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Step 3: Linked Properties
            'property_ids' => ['required', 'array', 'min:1'],
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
                    $auctionParam = $this->route('auction');
                    $currentAuctionId = is_object($auctionParam) ? (int) $auctionParam->id : (int) $auctionParam;

                    $property = Property::query()->find($value);
                    if (! $property) {
                        return;
                    }
                    $categoryValue = $this->input('category');
                    if (! $categoryValue) {
                        $auctionParam = $this->route('auction');
                        $categoryValue = is_object($auctionParam) ? $auctionParam->category : null;
                    }
                    $category = AssignmentCategory::tryFrom((string) $categoryValue);
                    if ($category && ! $category->allowsUsageClassification($property->usage_classification)) {
                        $fail("العقار ({$property->name}) تصنيفه ({$property->usage_classification_label}) لا يتطابق مع تصنيف المزاد المختار ({$category->label()}).");

                        return;
                    }
                    $isSoldInAnotherAuction = DB::table('auction_properties')
                        ->where('property_id', $value)
                        ->where('result_status', 'sold')
                        ->where('auction_id', '!=', $currentAuctionId)
                        ->exists();
                    if ($isSoldInAnotherAuction) {
                        $fail("العقار ({$property->name}) تم بيعه بالفعل في مزاد سابق ولا يمكن إدراجه في هذا المزاد.");

                        return;
                    }
                    $isInAnotherActiveAuction = Auction::query()->open()
                        ->whereHas('properties', fn ($query) => $query->where('properties.id', $value))
                        ->where('auctions.id', '!=', $currentAuctionId)
                        ->exists();
                    if ($isInAnotherActiveAuction) {
                        $fail("العقار ({$property->name}) مدرج حالياً في مزاد نشط.");
                    }
                },
            ],

            'property_results' => ['prohibited'],
            'marketing_evidence_meta' => ['prohibited'],
            'marketing_evidence' => ['prohibited'],
            'report_attachments' => ['prohibited'],
            'board_images_before' => ['prohibited'],
            'board_images_after' => ['prohibited'],
            'marketing' => ['nullable', 'array:introduction'],
            'marketing.introduction' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'report_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx'],
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
            'property_ids.required' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.min' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.*.exists' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار.',
            'property_ids.*.distinct' => 'لا يمكن اختيار العقار نفسه أكثر من مرة.',
            'platform_url.url' => 'رابط المنصة يجب أن يكون رابطاً صالحاً.',
            'project_manager_name.required' => 'اسم مدير المشروع مطلوب.',
            'report_image.image' => 'يجب أن تكون صورة التقرير ملف صورة صالحًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المزاد',
            'description' => 'وصف المزاد',
            'start_date' => 'تاريخ بداية المزاد',
            'start_time' => 'وقت البداية',
            'end_date' => 'تاريخ نهاية المزاد',
            'end_time' => 'وقت النهاية',
            'type' => 'نوع المزاد',
            'category' => 'تصنيف المزاد',
            'city' => 'المدينة الرئيسية',
            'expected_properties_count' => 'عدد العقارات المتوقع',
            'assignment_id' => 'الإسناد المرتبط بالمزاد',
            'supervising_entity' => 'الجهة المشرفة على المزاد',
            'project_manager_name' => 'اسم مدير المشروع',
            'platform_entity_id' => 'منصة المزاد',
            'platform_url' => 'رابط المزاد على المنصة',
            'property_ids' => 'العقارات المرتبطة بالإسناد المختار',
            'property_ids.*' => 'العقارات المرتبطة بالإسناد المختار',
            'property_results' => 'نتائج العقارات',
            'report_image' => 'صورة غلاف التقرير',
            'notes' => 'ملاحظات المنصة والمشروع',
            'cover_image' => 'صورة غلاف المزاد',
            'attachments' => 'ملفات إضافية',
            'attachments.*' => 'ملفات إضافية',
            'marketing' => 'بيانات التسويق',
            'marketing.introduction' => 'مقدمة التقرير',
            'marketing_evidence' => 'شواهد التسويق',
            'marketing_evidence_meta' => 'بيانات شواهد التسويق',
            'report_attachments' => 'مرفقات التقرير',
            'board_images_before' => 'صور اللوحات قبل التركيب',
            'board_images_after' => 'صور اللوحات بعد التركيب',
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
