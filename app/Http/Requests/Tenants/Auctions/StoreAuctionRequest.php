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
use App\Services\Tenants\Auctions\AuctionBrokersManager;
use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreAuctionRequest extends WizardFormRequest
{
    private ?Assignment $resolvedAssignment = null;

    private bool $assignmentResolved = false;

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('auctions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            // Step 1: Basic Info
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
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
                            $fail('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.system_date($assignment->end_date).').');
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
                        } catch (\Throwable) {
                        }
                    }
                },
            ],
            'type' => ['required', Rule::enum(AuctionType::class)],
            'category' => ['required', Rule::enum(AuctionCategory::class)],
            'city' => ['required', 'string', 'max:100'],
            'expected_properties_count' => ['required', 'integer', 'min:1'],

            // Auctioneer
            'auctioneer' => ['required', 'array'],
            'auctioneer.name' => ['required', 'string', 'max:255'],
            'auctioneer.national_id_or_cr' => ['required', 'string', 'digits:10'],
            'auctioneer.mobile' => ['required', 'string', 'max:15'],
            'auctioneer.email' => ['required', 'email', 'max:255'],

            // Step 2: Assignment & Platform & Funds
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
                        $fail('الإسناد المختار منتهي الصلاحية بتاريخ '.system_date($assignment->end_date).'.');

                        return;
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
            'platform_entity_id' => ['required', 'integer', Rule::exists('issuing_entities', 'id')->whereNull('deleted_at')],
            'platform_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'award_period' => ['required', 'integer', 'min:1'],
            'venue' => ['nullable', 'string', 'max:255'],

            // Funds
            'funds' => ['required', 'array'],
            'funds.receipt_method' => ['required', 'string', 'max:255'],
            'funds.receipt_days' => ['required', 'integer', 'min:0', 'max:365'],
            'funds.entry_type' => ['required', 'string', 'max:255'],
            'funds.refund_method' => ['required', 'string', 'max:255'],
            'funds.refund_days' => ['required', 'integer', 'min:0', 'max:3'],
            'funds.safekeeping' => ['required', 'string', 'max:255'],

            // Step 3: Linked Properties & Details
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
                    $property = Property::query()->find($value);
                    if (! $property) {
                        return;
                    }
                    $category = AssignmentCategory::tryFrom((string) $this->input('category'));
                    if ($category && ! $category->allowsUsageClassification($property->usage_classification)) {
                        $fail("العقار ({$property->name}) تصنيفه ({$property->usage_classification_label}) لا يتطابق مع تصنيف المزاد المختار ({$category->label()}).");

                        return;
                    }
                    if ($property->status === 'sold') {
                        $fail("العقار ({$property->name}) تم بيعه بالفعل في مزاد سابق ولا يمكن إدراجه في مزاد جديد.");

                        return;
                    }
                    $isSoldInPivot = DB::table('auction_properties')
                        ->where('property_id', $value)
                        ->where('result_status', 'sold')
                        ->exists();
                    if ($isSoldInPivot) {
                        $fail("العقار ({$property->name}) مسجل كمباع في مزاد سابق.");

                        return;
                    }
                    $isInActiveAuction = Auction::query()->open()
                        ->whereHas('properties', fn ($query) => $query->where('properties.id', $value))
                        ->exists();
                    if ($isInActiveAuction) {
                        $fail("العقار ({$property->name}) مدرج حالياً في مزاد نشط.");
                    }
                },
            ],
            'properties_data' => ['required', 'array', 'min:1'],
            'properties_data.*.opening_value' => ['required', 'numeric', 'min:0'],
            'properties_data.*.entry_amount' => ['required', 'numeric', 'min:0'],
            'properties_data.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'properties_data.*.ownership_share' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'properties_data.*.board_count' => ['required', 'integer', 'min:1'],
            'properties_data.*.board_dimensions' => ['required', 'string', 'max:255'],
            'properties_data.*.disclosure' => ['required', 'string', 'max:1000'],
            'properties_data.*.valuation' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg'],
            'properties_data.*.board_before' => ['nullable', 'array'],
            'properties_data.*.board_before.*' => ['file', 'max:10240', 'mimes:png,jpg,jpeg,webp'],

            'property_results' => ['prohibited'],
            'marketing_evidence_meta' => ['nullable', 'array'],
            'report_attachments' => ['prohibited'],
            'board_images_before' => ['nullable', 'array'],
            'board_images_after' => ['prohibited'],
            'marketing' => ['required', 'array:introduction,report_summary,planning_methodology,city_context,brochure_methodology,audience_methodology,messages_methodology,brochure_context,brochure_url,board_context,board_url,contract_text,brokers'],
            'marketing.introduction' => ['required', 'string', 'max:1000'],
            'marketing.report_summary' => ['nullable', 'string', 'max:1000'],
            'marketing.planning_methodology' => ['nullable', 'string', 'max:1000'],
            'marketing.city_context' => ['nullable', 'array'],
            'marketing.city_context.*' => ['required', 'string', 'max:100', 'distinct'],
            'marketing.brochure_methodology' => ['nullable', 'string', 'max:1000'],
            'marketing.audience_methodology' => ['nullable', 'string', 'max:1000'],
            'marketing.messages_methodology' => ['nullable', 'string', 'max:1000'],
            'marketing.brochure_context' => ['nullable', 'string', 'max:1000'],
            'marketing.brochure_url' => ['exclude'],
            'marketing.board_context' => ['nullable', 'string', 'max:1000'],
            'marketing.board_url' => ['nullable', 'url:https', 'max:2048'],
            'marketing.contract_text' => ['nullable', 'string', 'max:1000'],
            'marketing.brokers' => ['nullable', 'array', 'max:50'],
            'marketing.brokers.*' => ['nullable', 'array:key,name,evidence_id'],
            'marketing.brokers.*.key' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]{1,80}$/'],
            'marketing.brokers.*.name' => ['nullable', 'string', 'max:200'],
            'marketing.brokers.*.evidence_id' => ['nullable', 'integer'],

            // Marketing Campaigns
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(array_keys(config('auction-marketing-campaigns', [])))],
            'campaigns' => ['nullable', 'array'],
            'campaigns.*.campaign_date' => ['nullable', 'date'],
            'campaigns.*.url' => ['nullable', 'url', 'max:2048'],
            'campaigns.*.metrics' => ['nullable', 'array'],
            'campaigns.*.evidence' => ['nullable', 'array'],
            'campaigns.*.evidence.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,webp'],
            'marketing_evidence' => ['nullable', 'array'],
            'marketing_evidence.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,webp'],

            // Step 5: Disclosures
            'disclosures' => ['required', 'array'],
            'disclosures.disposition_rights' => ['required', 'array', 'min:1'],
            'disclosures.disposition_right_other' => ['nullable', 'string', 'max:255'],
            'disclosures.no_conflict_of_interest' => ['accepted'],
            'disclosures.information_accuracy' => ['accepted'],
            'disclosures.adherence_to_regulations' => ['accepted'],
            'brochure_evidence' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'platform_evidence' => ['nullable', 'array', 'max:50'],
            'platform_evidence.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'broker_evidence' => ['nullable', 'array', 'max:50'],
            'broker_evidence.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'report_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $marketing = (array) $this->input('marketing', []);
            $capacity = app(AuctionReportInputCapacity::class);
            foreach ([
                'planning_methodology',
                'brochure_methodology',
                'audience_methodology',
                'messages_methodology',
                'brochure_context',
                'board_context',
                'contract_text',
            ] as $field) {
                $val = $marketing[$field] ?? null;
                if (is_string($val) && trim($val) !== '' && ! $capacity->fits($field, $val)) {
                    $validator->errors()->add('marketing.'.$field, 'النص أطول من المساحة المتاحة في التقرير. اختصر النص ثم احفظ.');
                }
            }

            $links = app(AuctionReportLinks::class);
            foreach (['board_url'] as $urlField) {
                $url = $marketing[$urlField] ?? null;
                if (is_string($url) && trim($url) !== '' && $links->safeUrl($url) === null) {
                    $validator->errors()->add('marketing.'.$urlField, 'أدخل رابط HTTPS صالحًا دون بيانات دخول.');
                }
            }

            $brokerRows = (array) ($marketing['brokers'] ?? []);
            $brokerFiles = (array) $this->file('broker_evidence', []);
            if ($brokerRows !== [] || $brokerFiles !== []) {
                try {
                    app(AuctionBrokersManager::class)->plan(null, $brokerRows, $brokerFiles, 'marketing.brokers');
                } catch (ValidationException $error) {
                    $validator->errors()->merge($error->errors());
                }
            }

            $propertyIds = (array) $this->input('property_ids', []);
            $propertiesData = (array) $this->input('properties_data', []);
            $propertiesFiles = (array) $this->file('properties_data', []);
            foreach ($propertyIds as $pId) {
                if (! isset($propertiesData[$pId])) {
                    $validator->errors()->add("properties_data.{$pId}", 'بيانات العقار في المزاد غير مكتملة.');

                    continue;
                }
                $pRow = $propertiesData[$pId];
                $pFiles = $propertiesFiles[$pId] ?? [];
                $entry = (float) str_replace(',', '', (string) ($pRow['entry_amount'] ?? 0));
                $opening = (float) str_replace(',', '', (string) ($pRow['opening_value'] ?? 0));
                $estimated = isset($pRow['estimated_value']) && is_numeric(str_replace(',', '', (string) $pRow['estimated_value']))
                    ? (float) str_replace(',', '', (string) $pRow['estimated_value'])
                    : (float) (Property::find($pId)?->estimated_value ?? 0);

                if ($estimated > 0 && $opening > $estimated) {
                    $validator->errors()->add("properties_data.{$pId}.opening_value", 'قيمة افتتاح المزاد لا يمكن أن تتجاوز القيمة التقديرية للعقار.');
                }
                if ($entry > $opening) {
                    $validator->errors()->add("properties_data.{$pId}.entry_amount", 'مبلغ الدخول لا يمكن أن يكون أكبر من قيمة افتتاح المزاد.');
                }
                if (empty($pFiles['valuation']) && empty($pRow['valuation'])) {
                    $validator->errors()->add("properties_data.{$pId}.valuation", 'نموذج التقييم العقاري مطلوب لكل عقار.');
                }
                if (empty($pFiles['board_before']) && empty($pRow['board_before'])) {
                    $validator->errors()->add("properties_data.{$pId}.board_before", 'صور تركيب اللوحات مطلوبة لكل عقار.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            // Step 1: الأساسيات ومحرّج المزاد
            'name.required' => 'اسم المزاد مطلوب.',
            'name.max' => 'اسم المزاد يجب ألا يتجاوز 255 حرف.',
            'description.max' => 'وصف المزاد يجب ألا يتجاوز 1000 حرف.',
            'description.required' => 'وصف المزاد مطلوب.',
            'start_date.required' => 'تاريخ بداية المزاد مطلوب.',
            'start_time.required' => 'وقت بداية المزاد مطلوب.',
            'end_date.required' => 'تاريخ نهاية المزاد مطلوب.',
            'end_time.required' => 'وقت نهاية المزاد مطلوب.',
            'end_date.after_or_equal' => 'تاريخ نهاية المزاد يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'type.required' => 'نوع المزاد مطلوب.',
            'category.required' => 'تصنيف المزاد مطلوب.',
            'city.required' => 'المدينة الرئيسية مطلوبة.',
            'expected_properties_count.required' => 'عدد العقارات المتوقع مطلوب.',
            'expected_properties_count.min' => 'عدد العقارات المتوقع يجب أن يكون 1 على الأقل.',
            'auctioneer.required' => 'بيانات محرّج المزاد مطلوبة.',
            'auctioneer.name.required' => 'اسم محرّج المزاد مطلوب.',
            'auctioneer.national_id_or_cr.required' => 'رقم هوية محرّج المزاد مطلوب.',
            'auctioneer.national_id_or_cr.digits' => 'رقم هوية محرّج المزاد يجب أن يتكون من 10 أرقام.',
            'auctioneer.mobile.required' => 'رقم جوال محرّج المزاد مطلوب.',
            'auctioneer.email.required' => 'البريد الإلكتروني لمحرّج المزاد مطلوب.',
            'auctioneer.email.email' => 'البريد الإلكتروني لمحرّج المزاد غير صالح.',

            // Step 2: المنصة والأموال والإسناد
            'assignment_id.required' => 'يجب ربط المزاد بإسناد مسجل.',
            'assignment_id.exists' => 'الإسناد المختار غير موجود.',
            'platform_entity_id.required' => 'منصة المزاد مطلوبة.',
            'project_manager_name.required' => 'اسم مدير المشروع مطلوب.',
            'platform_url.url' => 'رابط المنصة يجب أن يكون رابطاً صالحاً.',
            'award_period.required' => 'مدة الترسية بالدقائق مطلوبة.',
            'award_period.min' => 'مدة الترسية يجب أن تكون دقيقة واحدة على الأقل.',
            'funds.required' => 'بيانات أموال المتنافسين مطلوبة.',
            'funds.receipt_method.required' => 'طريقة استلام أموال المتنافسين مطلوبة.',
            'funds.receipt_days.required' => 'مدة استلام أموال المتنافسين مطلوبة.',
            'funds.receipt_days.min' => 'مدة استلام الأموال لا يمكن أن تكون سالبة.',
            'funds.receipt_days.max' => 'مدة استلام الأموال لا يمكن أن تتجاوز 365 يوماً.',
            'funds.entry_type.required' => 'نوع مبلغ الدخول مطلوب.',
            'funds.refund_method.required' => 'طريقة إعادة الأموال مطلوبة.',
            'funds.refund_days.required' => 'مدة إعادة الأموال بالأيام مطلوبة.',
            'funds.refund_days.min' => 'مدة إعادة الأموال لا يمكن أن تكون سالبة.',
            'funds.refund_days.max' => 'مدة إعادة الأموال لا يمكن أن تتجاوز 3 أيام.',
            'funds.safekeeping.required' => 'آلية حفظ أموال المتنافسين مطلوبة.',

            // Step 3: العقارات في المزاد
            'property_ids.required' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.min' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.*.exists' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار.',
            'property_ids.*.distinct' => 'لا يمكن اختيار العقار نفسه أكثر من مرة.',
            'properties_data.required' => 'بيانات العقارات المختارة في المزاد مطلوبة.',
            'properties_data.min' => 'بيانات العقارات المختارة في المزاد مطلوبة.',
            'properties_data.*.opening_value.required' => 'قيمة افتتاح المزاد مطلوبة لكل عقار مختار.',
            'properties_data.*.opening_value.numeric' => 'قيمة افتتاح المزاد يجب أن تكون رقماً.',
            'properties_data.*.opening_value.min' => 'قيمة افتتاح المزاد لا يمكن أن تكون سالبة.',
            'properties_data.*.entry_amount.required' => 'مبلغ الدخول مطلوب لكل عقار مختار.',
            'properties_data.*.entry_amount.numeric' => 'مبلغ الدخول يجب أن يكون رقماً.',
            'properties_data.*.entry_amount.min' => 'مبلغ الدخول لا يمكن أن يكون سالباً.',
            'properties_data.*.ownership_share.required' => 'نسبة الملكية المعروضة مطلوبة لكل عقار.',
            'properties_data.*.ownership_share.numeric' => 'نسبة الملكية المعروضة يجب أن تكون رقماً.',
            'properties_data.*.ownership_share.min' => 'نسبة الملكية المعروضة يجب أن تكون أكبر من 0%.',
            'properties_data.*.ownership_share.max' => 'نسبة الملكية المعروضة لا يمكن أن تتجاوز 100%.',
            'properties_data.*.board_count.required' => 'عدد اللوحات مطلوب لكل عقار.',
            'properties_data.*.board_count.integer' => 'عدد اللوحات يجب أن يكون رقماً صحيحاً.',
            'properties_data.*.board_count.min' => 'عدد اللوحات يجب أن يكون لوحة واحدة على الأقل.',
            'properties_data.*.board_dimensions.required' => 'مقاس اللوحات مطلوب لكل عقار.',
            'properties_data.*.disclosure.required' => 'الإفصاح الخاص بالعقار مطلوب.',
            'properties_data.*.disclosure.max' => 'الإفصاح الخاص بالعقار يجب ألا يتجاوز 1000 حرف.',
            'properties_data.*.valuation.max' => 'حجم ملف نموذج التقييم يجب ألا يتجاوز 10 ميجابايت.',
            'properties_data.*.valuation.mimes' => 'ملف نموذج التقييم يجب أن يكون بصيغة: PDF, PNG, JPG, JPEG.',
            'properties_data.*.board_before.*.max' => 'حجم صورة اللوحة يجب ألا يتجاوز 10 ميجابايت.',
            'properties_data.*.board_before.*.mimes' => 'صور اللوحات يجب أن تكون بصيغة: PNG, JPG, JPEG, WEBP.',

            // Step 4: التسويق والحملات
            'marketing.introduction.required' => 'مقدمة التقرير مطلوبة.',
            'marketing.brochure_url.url' => 'رابط الكتيب التسويقي يجب أن يكون رابط HTTPS صالحاً.',
            'marketing.board_url.url' => 'رابط اللوحات يجب أن يكون رابط HTTPS صالحاً.',
            'channels.*.in' => 'قناة الحملة المختارة غير صالحة.',
            'campaigns.*.campaign_date.date' => 'تاريخ انطلاق الحملة يجب أن يكون تاريخاً صالحاً.',
            'campaigns.*.url.url' => 'رابط منشور أو تغطية الحملة يجب أن يكون رابطاً صالحاً.',
            'campaigns.*.evidence.*.max' => 'حجم ملف إثبات الحملة يجب ألا يتجاوز 10 ميجابايت.',
            'campaigns.*.evidence.*.mimes' => 'مرفقات إثبات الحملة يجب أن تكون بصيغة: PDF, PNG, JPG, JPEG, WEBP.',

            // Step 5: المرفقات والإفصاحات النظامية
            'disclosures.required' => 'الإفصاحات النظامية مطلوبة.',
            'disclosures.disposition_rights.required' => 'يجب تحديد حقوق التصرف والقيود النظامية.',
            'disclosures.disposition_rights.min' => 'يجب اختيار حق تصرف واحد على الأقل.',
            'disclosures.disposition_right_other.max' => 'تفاصيل حقوق التصرف الأخرى يجب ألا تتجاوز 255 حرف.',
            'disclosures.no_conflict_of_interest.accepted' => 'يجب الإقرار بعدم وجود تعارض مصالح للمتابعة.',
            'disclosures.information_accuracy.accepted' => 'يجب الإقرار بصحة ودقة المعلومات للمتابعة.',
            'disclosures.adherence_to_regulations.accepted' => 'يجب الإقرار بالالتزام بالأنظمة واللوائح للمتابعة.',
            'report_image.image' => 'يجب أن تكون صورة التقرير ملف صورة صالحًا.',
            'cover_image.image' => 'يجب أن تكون صورة غلاف المزاد ملف صورة صالحًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            // الأساسيات
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

            // محرّج المزاد
            'auctioneer' => 'بيانات محرّج المزاد',
            'auctioneer.name' => 'اسم محرّج المزاد',
            'auctioneer.national_id_or_cr' => 'رقم هوية محرّج المزاد',
            'auctioneer.mobile' => 'رقم جوال محرّج المزاد',
            'auctioneer.email' => 'البريد الإلكتروني لمحرّج المزاد',

            // المنصة والمشروع
            'assignment_id' => 'الإسناد المرتبط بالمزاد',
            'supervising_entity' => 'الجهة المشرفة على المزاد',
            'project_manager_name' => 'اسم مدير المشروع',
            'platform_entity_id' => 'منصة المزاد',
            'platform_url' => 'رابط المزاد على المنصة',
            'award_period' => 'مدة الترسية بالدقائق',
            'venue' => 'مقر إقامة المزاد',
            'notes' => 'ملاحظات المنصة والمشروع',

            // أموال المتنافسين
            'funds' => 'بيانات أموال المتنافسين',
            'funds.receipt_method' => 'طريقة استلام الأموال',
            'funds.receipt_days' => 'مدة استلام الأموال بالأيام',
            'funds.entry_type' => 'نوع مبلغ الدخول',
            'funds.refund_method' => 'طريقة إعادة الأموال',
            'funds.refund_days' => 'مدة إعادة الأموال بالأيام',
            'funds.safekeeping' => 'آلية حفظ الأموال',

            // العقارات وتفاصيلها في المزاد
            'property_ids' => 'العقارات المختارة للمزاد',
            'property_ids.*' => 'العقار المختار',
            'properties_data' => 'بيانات العقارات في المزاد',
            'properties_data.*' => 'بيانات العقار في المزاد',
            'properties_data.*.opening_value' => 'قيمة افتتاح المزاد',
            'properties_data.*.entry_amount' => 'مبلغ الدخول',
            'properties_data.*.estimated_value' => 'القيمة التقديرية للعقار',
            'properties_data.*.ownership_share' => 'نسبة الملكية المعروضة',
            'properties_data.*.board_count' => 'عدد اللوحات',
            'properties_data.*.board_dimensions' => 'مقاس اللوحات',
            'properties_data.*.disclosure' => 'الإفصاح الخاص بالعقار',
            'properties_data.*.valuation' => 'نموذج التقييم العقاري',
            'properties_data.*.board_before' => 'صور تركيب اللوحات',
            'properties_data.*.board_before.*' => 'صورة تركيب اللوحة',

            // التسويق والوسطاء
            'marketing' => 'بيانات التسويق',
            'marketing.introduction' => 'مقدمة التقرير',
            'marketing.report_summary' => 'ملخص التقرير',
            'marketing.planning_methodology' => 'منهجية التخطيط',
            'marketing.city_context' => 'نطاق الحملة الجغرافي',
            'marketing.city_context.*' => 'نطاق الحملة الجغرافي',
            'marketing.brochure_methodology' => 'إعداد الكتيب والهوية البصرية',
            'marketing.audience_methodology' => 'الفئات المستهدفة',
            'marketing.messages_methodology' => 'الرسائل والتواصل مع العملاء',
            'marketing.brochure_context' => 'تفاصيل الكتيب التسويقي',
            'marketing.brochure_url' => 'رابط الكتيب التسويقي',
            'marketing.board_context' => 'تفاصيل تركيب اللوحات',
            'marketing.board_url' => 'رابط اللوحات',
            'marketing.contract_text' => 'نص التعاقد مع الوسيط',
            'marketing.brokers' => 'عقود الوسطاء',
            'marketing.brokers.*.name' => 'اسم الوسيط',

            // الحملات التسويقية
            'channels' => 'قنوات الحملات التسويقية',
            'channels.*' => 'قناة الحملة التسويقية',
            'campaigns' => 'بيانات الحملات التسويقية',
            'campaigns.*.campaign_date' => 'تاريخ انطلاق الحملة',
            'campaigns.*.url' => 'رابط تغطية الحملة',
            'campaigns.*.metrics' => 'مقاييس أداء الحملة',
            'campaigns.*.evidence' => 'إثباتات وشواهد الحملة',
            'campaigns.*.evidence.*' => 'مرفق إثبات الحملة',

            // الشواهد والمرفقات
            'brochure_evidence' => 'ملف الكتيب الإلكتروني',
            'platform_evidence' => 'لقطات العرض على المنصة',
            'platform_evidence.*' => 'لقطة العرض على المنصة',
            'broker_evidence' => 'مرفقات عقود الوسطاء',
            'broker_evidence.*' => 'مرفق عقد الوسيط',
            'marketing_evidence' => 'شواهد التسويق',
            'marketing_evidence.*' => 'ملف شاهد التسويق',
            'marketing_evidence_meta' => 'بيانات شواهد التسويق',
            'report_attachments' => 'مرفقات التقرير',
            'board_images_before' => 'صور اللوحات قبل التركيب',
            'board_images_after' => 'صور اللوحات بعد التركيب',
            'cover_image' => 'صورة غلاف المزاد',
            'report_image' => 'صورة غلاف التقرير',
            'attachments' => 'ملفات إضافية',
            'attachments.*' => 'ملف إضافي',

            // الإفصاحات النظامية
            'disclosures' => 'الإفصاحات النظامية',
            'disclosures.disposition_rights' => 'حقوق التصرف والقيود النظامية',
            'disclosures.disposition_rights.*' => 'حق التصرف',
            'disclosures.disposition_right_other' => 'تفاصيل حقوق التصرف الأخرى',
            'disclosures.no_conflict_of_interest' => 'إقرار عدم وجود تعارض مصالح',
            'disclosures.information_accuracy' => 'إقرار صحة ودقة المعلومات',
            'disclosures.adherence_to_regulations' => 'إقرار الالتزام بالأنظمة واللوائح',

            'property_results' => 'نتائج العقارات',
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
