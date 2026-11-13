<?php

declare(strict_types=1);

namespace App\Services\Tenants\AuctionEstablishments;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AuctionEstablishmentRequestData
{
    public function sections(): array
    {
        return [
            'organization' => ['label' => 'المنشأة العقارية', 'fields' => [
                'name' => $this->field('اسم المنشأة', 'text', true, [], ['string', 'max:255', 'min:2'], ['maxlength' => '255']),
                'commercial_registration' => $this->field('السجل التجاري', 'text', true, [], ['string', 'digits:10', 'regex:/^\d{10}$/'], ['inputmode' => 'numeric', 'pattern' => '[0-9]{10}', 'maxlength' => '10', 'data-digits-only' => 'true', 'placeholder' => '10 أرقام']),
                'unified_number' => $this->field('الرقم الموحد للمنشأة (700)', 'text', true, [], ['string', 'digits:10', 'regex:/^700\d{7}$/'], ['inputmode' => 'numeric', 'pattern' => '700[0-9]{7}', 'maxlength' => '10', 'data-digits-only' => 'true', 'placeholder' => '700xxxxxxx', 'title' => 'الرقم الموحد يجب أن يبدأ بـ 700 ويتكون من 10 أرقام']),
                'fal_license' => $this->field('رخصة فال للمزادات العقارية', 'text', true, [], ['string', 'max:50', 'regex:/^\d+$/'], ['inputmode' => 'numeric', 'pattern' => '[0-9]+', 'data-digits-only' => 'true', 'placeholder' => 'أدخل رقم رخصة فال']),
            ]],
            'manager' => ['label' => 'مدير المزاد', 'fields' => $this->personFields()],
            'auctioneer' => ['label' => 'محرّج المزاد', 'fields' => $this->personFields()],
            'auction' => ['label' => 'تفاصيل إقامة المزاد', 'fields' => [
                'name' => $this->field('اسم المزاد', 'text', true, [], ['string', 'max:255', 'min:3'], ['maxlength' => '255']),
                'properties_count' => $this->field('عدد العقارات', 'number', true, [], ['integer', 'min:1'], ['min' => '1', 'step' => '1', 'inputmode' => 'numeric']),
                'units_count' => $this->field('عدد الوحدات', 'number', true, [], ['integer', 'min:0'], ['min' => '0', 'step' => '1', 'inputmode' => 'numeric']),
                'method' => $this->field('طريقة إقامة المزاد', 'select', true, ['electronic' => 'إلكتروني', 'in_person' => 'حضوري', 'hybrid' => 'هجين']),
                'platform' => $this->field('المنصة الإلكترونية', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
                'city' => $this->field('المدينة', 'text', true, [], ['string', 'max:100'], ['maxlength' => '100']),
                'venue' => $this->field('مقر إقامة المزاد', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
                'broadcast_url' => $this->field('رابط المزاد أو البث', 'url', false, [], ['url:http,https', 'max:2000'], ['placeholder' => 'https://...']),
                'start_date' => $this->field('تاريخ البداية', 'date', true, [], ['date']),
                'end_date' => $this->field('تاريخ النهاية', 'date', true, [], ['date', 'after_or_equal:data.auction.start_date']),
                'start_time' => $this->field('وقت البداية', 'time', true, [], ['date_format:H:i']),
                'award_period' => $this->field('مدة الترسية بالدقائق', 'number', true, [], ['integer', 'min:1'], ['min' => '1', 'step' => '1', 'inputmode' => 'numeric', 'placeholder' => 'أدخل مدة الترسية بالدقائق']),
                'disposition' => $this->field('نوع التصرف', 'select', true, ['sale' => 'بيع', 'lease' => 'تأجير']),
            ]],
            'funds' => ['label' => 'استلام وحفظ وإعادة أموال المتنافسين', 'fields' => [
                'receipt_method' => $this->field('طريقة الاستلام', 'select', true, $this->paymentMethods()),
                'receipt_other' => $this->field('طريقة الاستلام الأخرى', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
                'receipt_days' => $this->field('مدة الاستلام بالأيام', 'number', true, [], ['integer', 'min:0', 'max:365'], ['min' => '0', 'max' => '365', 'step' => '1', 'inputmode' => 'numeric']),
                'refund_method' => $this->field('طريقة الإعادة', 'select', true, $this->paymentMethods()),
                'refund_other' => $this->field('طريقة الإعادة الأخرى', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
                'refund_days' => $this->field('مدة الإعادة بالأيام', 'number', true, [], ['integer', 'min:0', 'max:3'], ['min' => '0', 'max' => '3', 'step' => '1', 'inputmode' => 'numeric', 'title' => 'مدة الإعادة يجب ألا تزيد عن 3 أيام']),
                'entry_type' => $this->field('نوع مبلغ الدخول', 'select', true, ['earnest' => 'عربون', 'advance' => 'مقدم ثمن', 'refundable' => 'مبلغ مسترد', 'none' => 'بدون مبلغ']),
                'safekeeping' => $this->field('آلية حفظ الأموال', 'text', true, [], ['string', 'max:255'], ['maxlength' => '255']),
            ]],
            'disclosures' => ['label' => 'الإفصاحات', 'fields' => $this->disclosureFields()],
            'declaration' => ['label' => 'الإقرار والتعهد', 'fields' => [
                'representative_name' => $this->field('اسم الممثل النظامي للمنشأة', 'text', true, [], ['string', 'max:255', 'min:3'], ['maxlength' => '255']),
            ]],
        ];
    }

    public function propertyFields(): array
    {
        return [
            'owner_name' => $this->field('المالك / الملاك', 'text', true, [], ['string', 'max:255', 'min:3'], ['maxlength' => '255']),
            'deed_number' => $this->field('رقم الصك / الصحيفة العقارية', 'text', true, [], ['string', 'max:50', 'regex:/^\d+$/'], ['inputmode' => 'numeric', 'pattern' => '[0-9]+', 'data-digits-only' => 'true', 'placeholder' => 'أدخل رقم الصك']),
            'agent_name' => $this->field('اسم الوكيل', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
            'agency_number' => $this->field('رقم الوكالة', 'text', false, [], ['string', 'max:50', 'regex:/^\d+$/'], ['inputmode' => 'numeric', 'pattern' => '[0-9]+', 'data-digits-only' => 'true', 'placeholder' => 'أدخل رقم الوكالة']),
            'contract_number' => $this->field('رقم عقد المزاد', 'text', true, [], ['string', 'max:50', 'regex:/^\d+$/'], ['inputmode' => 'numeric', 'pattern' => '[0-9]+', 'data-digits-only' => 'true', 'placeholder' => 'أدخل رقم عقد المزاد']),
            'area' => $this->field('المساحة', 'number', true, [], ['numeric', 'gt:0', 'max:999999999'], ['min' => '0.01', 'step' => 'any', 'inputmode' => 'decimal']),
            'type' => $this->field('نوع العقار', 'text', true, [], ['string', 'max:100'], ['maxlength' => '100']),
            'usage' => $this->field('استخدام العقار', 'text', true, [], ['string', 'max:100'], ['maxlength' => '100']),
            'usage_other' => $this->field('الاستخدام الآخر', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
            'age' => $this->field('عمر العقار بالسنوات (صفر للأرض)', 'number', true, [], ['integer', 'min:0', 'max:200'], ['min' => '0', 'max' => '200', 'step' => '1', 'inputmode' => 'numeric']),
            'city_district' => $this->field('المدينة / الحي', 'text', true, [], ['string', 'max:255'], ['maxlength' => '255']),
            'status' => $this->field('الحالة النظامية', 'select', true, ['clear' => 'سليم', 'seized' => 'محجوز', 'mortgaged' => 'مرهون', 'endowed' => 'وقف', 'other' => 'أخرى']),
            'status_other' => $this->field('تفاصيل الحالة الأخرى', 'text', false, [], ['string', 'max:255'], ['maxlength' => '255']),
            'ownership_share' => $this->field('نسبة الملكية المعروضة %', 'number', true, [], ['numeric', 'gt:0', 'max:100'], ['min' => '0.01', 'max' => '100', 'step' => 'any', 'inputmode' => 'decimal']),
            'disclosure' => $this->field('الإفصاح الخاص بالعقار', 'text', true, [], ['string', 'max:2000']),
            'opening_value' => $this->field('قيمة افتتاح المزاد', 'number', true, [], ['numeric', 'min:0', 'max:999999999999'], ['min' => '0', 'step' => '0.01', 'inputmode' => 'decimal']),
            'entry_amount' => $this->field('مبلغ الدخول', 'number', true, [], ['numeric', 'min:0', 'max:999999999999'], ['min' => '0', 'step' => '0.01', 'inputmode' => 'decimal']),
            'estimated_value' => $this->field('القيمة التقديرية', 'number', true, [], ['numeric', 'min:0', 'max:999999999999'], ['min' => '0', 'step' => '0.01', 'inputmode' => 'decimal']),
        ];
    }

    public function rules(): array
    {
        $rules = ['data' => ['required', 'array'], 'data.properties' => ['required', 'array', 'min:1'], 'data.properties.*.property_id' => ['required', 'integer', 'distinct']];
        foreach ($this->sections() as $section => $definition) {
            $rules['data.'.$section] = ['required', 'array'];
            foreach ($definition['fields'] as $key => $field) {
                $rules['data.'.$section.'.'.$key] = $field['rules'];
            }
        }
        foreach ($this->propertyFields() as $key => $field) {
            $rules['data.properties.*.'.$key] = $field['rules'];
        }
        $rules['data.disclosures.disposition_rights'] = ['required', 'array', 'min:1'];
        $rules['data.disclosures.disposition_rights.*'] = ['string', 'distinct', Rule::in(array_keys($this->dispositionRights()))];
        foreach (['receipt', 'refund'] as $method) {
            $rules['data.funds.'.$method.'_other'][] = 'required_if:data.funds.'.$method.'_method,other';
        }
        $rules['data.disclosures.disposition_right_other'][] = 'required_if:data.disclosures.disposition_rights.*,other';
        $rules['data.properties.*.status_other'][] = 'required_if:data.properties.*.status,other';
        $rules['data.properties.*.agency_number'][] = 'required_with:data.properties.*.agent_name';
        $rules['data.properties.*.agent_name'][] = 'required_with:data.properties.*.agency_number';
        $rules['data.auction.platform'][] = 'required_if:data.auction.method,electronic,hybrid';
        $rules['data.auction.venue'][] = 'required_if:data.auction.method,in_person,hybrid';

        return $rules;
    }

    public function dispositionRights(): array
    {
        return ['none' => 'لا يوجد', 'preemption' => 'شفعة', 'session_option' => 'خيار المجلس', 'condition_option' => 'خيار الشرط', 'earnest_sale' => 'بيع العربون', 'other' => 'أخرى'];
    }

    public function attachmentTypes(): array
    {
        return ['manager_certificate' => 'شهادة دورة مدير المزاد', 'auctioneer_certificate' => 'شهادة دورة محرّج المزاد', 'deed' => 'وثيقة الملكية', 'valuation' => 'نموذج التقييم العقاري', 'representative_authority' => 'مستند صفة الوكيل / معتمد العقد'];
    }

    public function attributes(): array
    {
        $attributes = [
            'data' => 'البيانات',
            'data.organization' => 'المنشأة العقارية',
            'data.manager' => 'مدير المزاد',
            'data.auctioneer' => 'محرّج المزاد',
            'data.auction' => 'تفاصيل إقامة المزاد',
            'data.funds' => 'أموال المتنافسين',
            'data.disclosures' => 'الإفصاحات',
            'data.declaration' => 'الإقرار والتعهد',
            'data.properties' => 'عقارات المزاد',
            'data.properties.*' => 'بيانات العقار',
            'data.properties.*.property_id' => 'العقار المختار',
            'declaration_accepted' => 'الإقرار والتعهد',
            'attachments' => 'المرفقات',
            'attachments.manager_certificate' => 'شهادة دورة مدير المزاد',
            'attachments.auctioneer_certificate' => 'شهادة دورة محرّج المزاد',
            'attachments.deed' => 'وثائق الملكية',
            'attachments.deed.*' => 'وثيقة الملكية',
            'attachments.valuation' => 'نماذج التقييم العقاري',
            'attachments.valuation.*' => 'نموذج التقييم العقاري',
            'attachments.representative_authority' => 'مستندات صفة الوكيل / معتمد العقد',
            'attachments.representative_authority.*' => 'مستند صفة الوكيل / معتمد العقد',
        ];

        foreach ($this->sections() as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $label = match (true) {
                    $sectionKey === 'manager' && $fieldKey === 'name' => 'اسم مدير المزاد',
                    $sectionKey === 'manager' && $fieldKey === 'identity_number' => 'رقم هوية مدير المزاد',
                    $sectionKey === 'manager' && $fieldKey === 'mobile' => 'رقم جوال مدير المزاد',
                    $sectionKey === 'manager' && $fieldKey === 'email' => 'البريد الإلكتروني لمدير المزاد',
                    $sectionKey === 'auctioneer' && $fieldKey === 'name' => 'اسم محرّج المزاد',
                    $sectionKey === 'auctioneer' && $fieldKey === 'identity_number' => 'رقم هوية محرّج المزاد',
                    $sectionKey === 'auctioneer' && $fieldKey === 'mobile' => 'رقم جوال محرّج المزاد',
                    $sectionKey === 'auctioneer' && $fieldKey === 'email' => 'البريد الإلكتروني لمحرّج المزاد',
                    $sectionKey === 'auction' && $fieldKey === 'name' => 'اسم المزاد',
                    $sectionKey === 'auction' && $fieldKey === 'start_date' => 'تاريخ بداية المزاد',
                    $sectionKey === 'auction' && $fieldKey === 'end_date' => 'تاريخ نهاية المزاد',
                    $sectionKey === 'auction' && $fieldKey === 'start_time' => 'وقت بداية المزاد',
                    $sectionKey === 'auction' && $fieldKey === 'city' => 'المدينة',
                    default => $field['label'],
                };
                $attributes['data.'.$sectionKey.'.'.$fieldKey] = $label;
                $attributes[$sectionKey.'.'.$fieldKey] = $label;
            }
        }

        $attributes['data.disclosures.disposition_rights'] = 'حقوق التصرف';
        $attributes['data.disclosures.disposition_rights.*'] = 'حق التصرف';

        foreach ($this->propertyFields() as $fieldKey => $field) {
            $attributes['data.properties.*.'.$fieldKey] = $field['label'];
            $attributes['properties.*.'.$fieldKey] = $field['label'];
            $attributes[$fieldKey] = $field['label'];
        }

        return $attributes;
    }

    public function defaults(Auction $auction): array
    {
        $auction->loadMissing(['properties.owners', 'properties.units', 'establishmentRequest']);
        $data = $auction->establishmentRequest?->data ?? [];
        $tenant = app()->bound('centralTenant') ? app('centralTenant') : tenant();
        $data['organization'] = array_replace($data['organization'] ?? [], [
            'name' => (string) $tenant?->company_name,
            'commercial_registration' => (string) $tenant?->commercial_number,
        ]);
        $data['manager']['name'] ??= $auction->project_manager_name;
        foreach ($this->auctionSource($auction) as $key => $value) {
            if (filled($value)) {
                $data['auction'][$key] = $value;
            }
        }
        $data['auction']['units_count'] ??= (string) $auction->properties->sum(fn ($property) => $property->units->count());
        $savedProperties = collect($data['properties'] ?? [])->keyBy('property_id');
        $data['properties'] = $auction->properties->map(function ($property) use ($savedProperties): array {
            $row = $savedProperties->get($property->id, []);
            $inherited = [
                'property_id' => $property->id, 'owner_name' => $property->owners->pluck('owner_name')->implode('، '),
                'deed_number' => $property->deed_number, 'area' => $property->area, 'type' => $property->getRawOriginal('type') ? $property->type_label : null,
                'usage' => $property->usage_classification ? $property->usage_classification_label : null, 'city_district' => trim($property->city.' / '.$property->district, ' /'),
                'agent_name' => $property->agent_name, 'agency_number' => $property->agency_number,
            ];
            foreach ($inherited as $key => $value) {
                if (filled($value)) {
                    $row[$key] = $value;
                }
            }
            $row['opening_value'] ??= $property->pivot->opening_price;
            $row['estimated_value'] ??= $property->estimated_value;

            return $row;
        })->values()->all();

        return $data;
    }

    public function snapshot(Auction $auction, array $input): array
    {
        $defaults = $this->defaults($auction);
        $rows = collect($input['properties'] ?? []);
        $ids = $auction->properties->modelKeys();
        $submittedIds = $rows->pluck('property_id')->map(fn ($id): int => (int) $id)->all();
        if ($ids === [] || count($submittedIds) !== count(array_unique($submittedIds)) || array_diff($ids, $submittedIds) || array_diff($submittedIds, $ids)) {
            throw ValidationException::withMessages(['data.properties' => 'يجب استكمال بيانات جميع عقارات المزاد فقط.']);
        }
        $data = [];
        foreach ($this->sections() as $section => $definition) {
            $data[$section] = Arr::only($input[$section] ?? [], array_keys($definition['fields']));
        }
        $data['organization']['name'] = $defaults['organization']['name'];
        $data['organization']['commercial_registration'] = $defaults['organization']['commercial_registration'];
        if (filled($auction->project_manager_name)) {
            $data['manager']['name'] = $auction->project_manager_name;
        }
        foreach ($this->auctionSource($auction) as $key => $value) {
            if (filled($value)) {
                $data['auction'][$key] = $value;
            }
        }
        $data['properties'] = [];
        $data['owner_relationships'] = [];
        foreach ($defaults['properties'] as $source) {
            $row = Arr::only($rows->firstWhere('property_id', $source['property_id']), array_keys($this->propertyFields()));
            $property = $auction->properties->firstWhere('id', $source['property_id']);
            $inherited = [
                'owner_name' => $property->owners->pluck('owner_name')->implode('، '), 'deed_number' => $property->deed_number,
                'area' => $property->area, 'type' => $property->getRawOriginal('type') ? $property->type_label : null,
                'usage' => $property->usage_classification ? $property->usage_classification_label : null,
                'city_district' => trim($property->city.' / '.$property->district, ' /'),
                'agent_name' => $property->agent_name, 'agency_number' => $property->agency_number,
            ];
            foreach ($inherited as $key => $value) {
                if (filled($value)) {
                    $row[$key] = $value;
                }
            }
            $row['property_id'] = $property->id;
            $data['properties'][] = $row;
            foreach ($property->owners as $owner) {
                $data['owner_relationships'][] = ['property_id' => $property->id, 'owner_id' => $owner->id, 'owner_name' => $owner->owner_name, 'national_id_or_cr' => $owner->national_id_or_cr];
            }
        }
        $data['disclosures']['disposition_rights'] = array_values($input['disclosures']['disposition_rights'] ?? []);
        foreach (['real_rights', 'dispute', 'value_information', 'off_plan', 'contribution'] as $key) {
            $details = trim((string) ($input['disclosures'][$key.'_details'] ?? ($input['disclosures'][$key] ?? '')));
            $hasVal = filled($details);
            $data['disclosures'][$key] = $hasVal ? 'yes' : 'no';
            $data['disclosures'][$key.'_details'] = $hasVal ? $details : '';
        }
        $data['submission_date'] = now()->format('Y/m/d');
        $data['property'] = Arr::except($data['properties'][0], ['property_id']);
        $data['agents'] = collect($data['properties'])->filter(fn ($row) => filled($row['agent_name'] ?? null))->map(fn ($row) => Arr::only($row, ['property_id', 'owner_name', 'deed_number', 'agent_name', 'agency_number']) + ['name' => $row['agent_name']])->values()->all();

        return $data;
    }

    public function auctionSource(Auction $auction): array
    {
        return [
            'name' => $auction->name, 'method' => $auction->type instanceof \BackedEnum ? $auction->type->value : $auction->type,
            'properties_count' => (string) $auction->properties->count(),
            'city' => $auction->city, 'platform' => $auction->platform_name, 'broadcast_url' => $auction->platform_url,
            'start_date' => $auction->start_date?->format('Y-m-d'), 'end_date' => $auction->end_date?->format('Y-m-d'),
            'start_time' => substr((string) $auction->start_time, 0, 5),
        ];
    }

    private function personFields(): array
    {
        return [
            'name' => $this->field('الاسم', 'text', true, [], ['string', 'max:255', 'min:3'], ['maxlength' => '255', 'placeholder' => 'الاسم الثلاثي']),
            'identity_number' => $this->field('رقم الهوية', 'text', true, [], ['string', 'digits:10', 'regex:/^[12]\d{9}$/'], ['inputmode' => 'numeric', 'pattern' => '[12][0-9]{9}', 'maxlength' => '10', 'data-digits-only' => 'true', 'placeholder' => '1xxxxxxxxx أو 2xxxxxxxxx', 'title' => 'رقم الهوية يجب أن يبدأ بـ 1 أو 2 ويتكون من 10 أرقام']),
            'mobile' => $this->field('رقم الجوال', 'tel', true, [], ['string', 'size:10', 'regex:/^05[0-9]{8}$/'], ['inputmode' => 'numeric', 'pattern' => '05[0-9]{8}', 'maxlength' => '10', 'data-phone-format' => 'mobile', 'placeholder' => '05xxxxxxxx', 'title' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام']),
            'email' => $this->field('البريد الإلكتروني', 'email', true, [], ['email', 'max:255'], ['maxlength' => '255', 'placeholder' => 'name@example.com']),
        ];
    }

    private function disclosureFields(): array
    {
        $fields = ['disposition_right_other' => $this->field('تفاصيل حقوق التصرف الأخرى', 'text', false, [], ['string', 'max:2000'], ['maxlength' => '2000'])];
        foreach (['real_rights' => 'حقوق عينية', 'dispute' => 'نزاع على العقار', 'value_information' => 'معلومات مؤثرة على القيمة', 'off_plan' => 'بيع على الخارطة', 'contribution' => 'مساهمة عقارية'] as $key => $label) {
            $fields[$key] = $this->field($label, 'select', true, ['no' => 'لا', 'yes' => 'نعم']);
            $fields[$key.'_details'] = $this->field('تفاصيل '.$label, 'text', false, [], ['string', 'max:2000'], ['maxlength' => '2000']);
        }

        return $fields;
    }

    private function paymentMethods(): array
    {
        return ['transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى'];
    }

    private function field(string $label, string $type = 'text', bool $required = true, array $options = [], array $rules = [], array $attrs = []): array
    {
        $defaultRules = match ($type) {
            'date' => ['date'],
            'time' => ['date_format:H:i'],
            'email' => ['email', 'max:255'],
            'url' => ['url:http,https', 'max:2000'],
            'select' => ['string', Rule::in(array_keys($options))],
            'number' => ['numeric'],
            default => ['string', 'max:255'],
        };

        $effectiveRules = ! empty($rules) ? $rules : $defaultRules;

        return [
            'label' => $label,
            'type' => $type,
            'required' => $required,
            'options' => $options,
            'rules' => [$required ? 'required' : 'nullable', ...$effectiveRules],
            'attrs' => $attrs,
        ];
    }
}
