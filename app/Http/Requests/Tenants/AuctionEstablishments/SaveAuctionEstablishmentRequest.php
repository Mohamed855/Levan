<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\AuctionEstablishments;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

final class SaveAuctionEstablishmentRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        if (! $auction || $auction->isClosed() || $auction->establishmentRequest?->isApproved()) {
            return false;
        }
        if ($this->filled('_wizard_resource_key') && $this->input('_wizard_resource_key') !== 'tenant.auction-establishment-request.'.$auction->getKey()) {
            return false;
        }

        return $this->user('tenant')?->can($auction->establishmentRequest ? 'auction_establishment_requests.edit' : 'auction_establishment_requests.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->input('data');
        if (is_array($data)) {
            $normalizeDigits = static fn (?string $v): string => str_replace(
                ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                (string) $v
            );

            if (isset($data['manager']['mobile'])) {
                $data['manager']['mobile'] = preg_replace('/\D+/', '', $normalizeDigits($data['manager']['mobile']));
            }
            if (isset($data['manager']['identity_number'])) {
                $data['manager']['identity_number'] = preg_replace('/\D+/', '', $normalizeDigits($data['manager']['identity_number']));
            }
            if (isset($data['auctioneer']['mobile'])) {
                $data['auctioneer']['mobile'] = preg_replace('/\D+/', '', $normalizeDigits($data['auctioneer']['mobile']));
            }
            if (isset($data['auctioneer']['identity_number'])) {
                $data['auctioneer']['identity_number'] = preg_replace('/\D+/', '', $normalizeDigits($data['auctioneer']['identity_number']));
            }
            if (isset($data['organization']['unified_number'])) {
                $data['organization']['unified_number'] = preg_replace('/\D+/', '', $normalizeDigits($data['organization']['unified_number']));
            }
            if (isset($data['organization']['commercial_registration'])) {
                $data['organization']['commercial_registration'] = preg_replace('/\D+/', '', $normalizeDigits($data['organization']['commercial_registration']));
            }
            if (isset($data['organization']['fal_license'])) {
                $data['organization']['fal_license'] = preg_replace('/\D+/', '', $normalizeDigits($data['organization']['fal_license']));
            }
            if (isset($data['properties']) && is_array($data['properties'])) {
                foreach ($data['properties'] as $idx => $prop) {
                    if (is_array($prop)) {
                        foreach (['area', 'opening_value', 'entry_amount', 'estimated_value', 'ownership_share', 'board_count'] as $numKey) {
                            if (isset($prop[$numKey]) && is_string($prop[$numKey])) {
                                $data['properties'][$idx][$numKey] = str_replace(',', '', $normalizeDigits($prop[$numKey]));
                            }
                        }
                        if (isset($prop['deed_number'])) {
                            $data['properties'][$idx]['deed_number'] = preg_replace('/\D+/', '', $normalizeDigits($prop['deed_number']));
                        }
                        if (isset($prop['contract_number'])) {
                            $data['properties'][$idx]['contract_number'] = trim($normalizeDigits($prop['contract_number']));
                        }
                    }
                }
            }
            if (isset($data['disclosures']) && is_array($data['disclosures'])) {
                foreach (['real_rights', 'dispute', 'value_information', 'off_plan', 'contribution'] as $key) {
                    $details = trim((string) ($data['disclosures'][$key.'_details'] ?? ($data['disclosures'][$key] ?? '')));
                    $hasVal = filled($details);
                    $data['disclosures'][$key] = $hasVal ? 'yes' : 'no';
                    $data['disclosures'][$key.'_details'] = $hasVal ? $details : '';
                }
            }
            $this->merge(['data' => $data, 'declaration_accepted' => '1']);
        } else {
            $this->merge(['declaration_accepted' => '1']);
        }
    }

    public function rules(?Auction $auction = null): array
    {
        $auction ??= $this->route('auction');
        $rules = app(AuctionEstablishmentRequestData::class)->rules();
        foreach (['approval_number', 'approved_at', 'approved_by', 'created_by', 'updated_by', 'submitted_at'] as $field) {
            $rules[$field] = ['prohibited'];
            $rules['data.'.$field] = ['prohibited'];
        }
        $rules['data.attachments'] = ['prohibited'];
        $rules['attachments'] = ['nullable', 'array:manager_certificate,auctioneer_certificate,valuation'];
        $rules['remove_attachments'] = ['nullable', 'array:valuation'];
        foreach (['manager_certificate', 'auctioneer_certificate'] as $type) {
            $rules['remove_attachments_'.$type] = ['nullable', 'boolean'];
            $hasExisting = filled($auction?->establishmentRequest?->data['attachments'][$type][0]['path'] ?? null)
                && ! $this->boolean('remove_attachments_'.$type);
            $rules['attachments.'.$type] = [$hasExisting ? 'nullable' : 'required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'];
        }
        $rules['remove_attachments.valuation'] = ['nullable', 'array'];
        $rules['remove_attachments.valuation.*'] = ['boolean'];
        $rules['attachments.valuation'] = ['nullable', 'array'];
        $rules['attachments.valuation.*'] = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'];
        $rules['board_images_before'] = ['nullable', 'array'];
        $rules['board_images_before.*'] = ['nullable', 'array', 'max:50'];
        $rules['board_images_before.*.*'] = ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'];
        $rules['remove_board_before_evidence'] = ['nullable', 'array'];
        $rules['remove_board_before_evidence.*'] = ['integer', 'distinct'];
        $rules['declaration_accepted'] = ['accepted'];

        return $rules;
    }

    public function after(?Auction $auction = null): array
    {
        return [function (Validator $validator) use ($auction): void {
            $schema = app(AuctionEstablishmentRequestData::class);
            $missingSettings = $schema->missingSettingsFields();
            if ($missingSettings !== []) {
                $validator->errors()->add(
                    'data.organization',
                    'يجب استكمال بيانات المنشأة في إعدادات النظام أولاً قبل تقديم الطلب: '.implode('، ', $missingSettings).'.'
                );
            }

            $rights = $this->input('data.disclosures.disposition_rights', []);
            if (is_array($rights) && in_array('none', $rights, true) && count($rights) > 1) {
                $validator->errors()->add('data.disclosures.disposition_rights', 'لا يمكن جمع عدم وجود حقوق مع حقوق أخرى.');
            }
            if (is_array($rights) && in_array('other', $rights, true) && blank($this->input('data.disclosures.disposition_right_other'))) {
                $validator->errors()->add('data.disclosures.disposition_right_other', 'أدخل تفاصيل حقوق التصرف الأخرى.');
            }

            $resolvedAuction = $auction ?? $this->route('auction');
            if (! ($resolvedAuction instanceof Auction) && is_numeric($resolvedAuction)) {
                $resolvedAuction = Auction::find((int) $resolvedAuction);
            }

            $capacity = app(AuctionReportInputCapacity::class);
            $properties = $this->input('data.properties', []);
            if (is_array($properties)) {
                foreach ($properties as $idx => $prop) {
                    if (! is_array($prop)) {
                        continue;
                    }
                    if (isset($prop['entry_amount'], $prop['opening_value'])) {
                        $entry = (float) str_replace(',', '', (string) $prop['entry_amount']);
                        $opening = (float) str_replace(',', '', (string) $prop['opening_value']);
                        if ($entry > $opening) {
                            $validator->errors()->add("data.properties.{$idx}.entry_amount", 'مبلغ الدخول لا يمكن أن يكون أكبر من قيمة افتتاح المزاد.');
                        }
                    }
                    if (isset($prop['entry_amount'], $prop['estimated_value'])) {
                        $entry = (float) str_replace(',', '', (string) $prop['entry_amount']);
                        $estimated = (float) str_replace(',', '', (string) $prop['estimated_value']);
                        if ($entry > $estimated) {
                            $validator->errors()->add("data.properties.{$idx}.entry_amount", 'مبلغ الدخول لا يمكن أن يكون أكبر من القيمة التقديرية للعقار.');
                        }
                    }
                    if (is_string($prop['board_dimensions'] ?? null) && trim($prop['board_dimensions']) !== '' && ! $capacity->fits('board_dimensions', $prop['board_dimensions'])) {
                        $validator->errors()->add("data.properties.{$idx}.board_dimensions", 'مقاس اللوحات أطول من المساحة المتاحة في التقرير.');
                    }
                    $propId = (int) ($prop['property_id'] ?? 0);
                    if ($propId > 0 && ! $this->hasBoardBeforeImagesForProperty($propId, $resolvedAuction)) {
                        $validator->errors()->add("board_images_before.{$propId}", 'صور تركيب اللوحات مطلوبة لكل عقار.');
                    }
                    if ($propId > 0 && ! $this->hasValuationForProperty($propId, $resolvedAuction)) {
                        $validator->errors()->add("attachments.valuation.{$propId}", 'نموذج التقييم العقاري مطلوب لكل عقار.');
                    }
                }
            }
        }];
    }

    public function hasValuationForProperty(int $propertyId, ?Auction $auction = null): bool
    {
        $file = $this->file("attachments.valuation.{$propertyId}")
            ?? ($this->allFiles()['attachments']['valuation'][$propertyId] ?? null);

        if ($file instanceof UploadedFile && $file->isValid()) {
            return true;
        }

        $inputVal = $this->input("attachments.valuation.{$propertyId}");
        if ($inputVal instanceof UploadedFile && $inputVal->isValid()) {
            return true;
        }

        $auction ??= $this->route('auction');
        if ($auction instanceof Auction && $auction->exists) {
            $hasExisting = filled($auction->establishmentRequest?->data['attachments']['valuation'][$propertyId]['path'] ?? null)
                && ! $this->boolean("remove_attachments.valuation.{$propertyId}");

            if ($hasExisting) {
                return true;
            }
        }

        return false;
    }

    public function hasBoardBeforeImagesForProperty(int $propertyId, ?Auction $auction = null): bool
    {
        $files = $this->file("board_images_before.{$propertyId}")
            ?? ($this->allFiles()['board_images_before'][$propertyId] ?? null);

        if ($files instanceof UploadedFile && $files->isValid()) {
            return true;
        }
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    return true;
                }
            }
        }

        $inputImages = $this->input("board_images_before.{$propertyId}");
        if (! empty($inputImages)) {
            if (is_array($inputImages)) {
                foreach ($inputImages as $img) {
                    if ($img instanceof UploadedFile || (is_string($img) && filled($img))) {
                        return true;
                    }
                }
            } elseif (is_string($inputImages) && filled($inputImages)) {
                return true;
            }
        }

        $auction ??= $this->route('auction');
        if ($auction instanceof Auction && $auction->exists) {
            $removedIds = array_map('intval', (array) $this->input('remove_board_before_evidence', []));
            $existingCount = $auction->attachments()
                ->where('report_section', 'board_before')
                ->where('property_id', $propertyId)
                ->whereNotIn('id', $removedIds)
                ->count();

            if ($existingCount > 0) {
                return true;
            }
        }

        return false;
    }

    public function attributes(): array
    {
        return app(AuctionEstablishmentRequestData::class)->attributes();
    }

    public function messages(): array
    {
        return [
            'declaration_accepted.accepted' => 'يجب الموافقة على الإقرار والتعهد لتقديم الطلب.',
            'data.properties.required' => 'يجب استكمال بيانات عقارات المزاد.',
            'data.properties.min' => 'يجب أن يحتوي المزاد على عقار واحد على الأقل.',
            'data.organization.commercial_registration.digits' => 'السجل التجاري يجب أن يتكون من 10 أرقام.',
            'data.organization.commercial_registration.regex' => 'السجل التجاري يجب أن يتكون من 10 أرقام.',
            'data.organization.unified_number.digits' => 'الرقم الموحد للمنشأة يجب أن يتكون من 10 أرقام.',
            'data.organization.unified_number.regex' => 'الرقم الموحد للمنشأة يجب أن يبدأ بـ 700 ويتكون من 10 أرقام.',
            'data.organization.fal_license.regex' => 'رخصة فال للمزادات العقارية يجب أن تحتوي على أرقام فقط.',
            'data.manager.identity_number.digits' => 'رقم هوية مدير المزاد يجب أن يتكون من 10 أرقام.',
            'data.manager.identity_number.regex' => 'رقم هوية مدير المزاد يجب أن يبدأ بـ 1 أو 2 ويتكون من 10 أرقام.',
            'data.manager.mobile.size' => 'رقم جوال مدير المزاد يجب أن يتكون من 10 أرقام.',
            'data.manager.mobile.regex' => 'رقم جوال مدير المزاد يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.',
            'data.manager.email.email' => 'البريد الإلكتروني لمدير المزاد غير صالح.',
            'data.auctioneer.identity_number.digits' => 'رقم هوية محرّج المزاد يجب أن يتكون من 10 أرقام.',
            'data.auctioneer.identity_number.regex' => 'رقم هوية محرّج المزاد يجب أن يبدأ بـ 1 أو 2 ويتكون من 10 أرقام.',
            'data.auctioneer.mobile.size' => 'رقم جوال محرّج المزاد يجب أن يتكون من 10 أرقام.',
            'data.auctioneer.mobile.regex' => 'رقم جوال محرّج المزاد يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.',
            'data.auctioneer.email.email' => 'البريد الإلكتروني لمحرّج المزاد غير صالح.',
            'data.auction.end_date.after_or_equal' => 'تاريخ نهاية المزاد يجب أن يكون في نفس يوم تاريخ البداية أو بعده.',
            'data.funds.refund_days.max' => 'مدة إعادة الأموال يجب ألا تزيد عن 3 أيام نظامياً.',
            'data.properties.*.deed_number.regex' => 'رقم الصك يجب أن يحتوي على أرقام فقط.',
            'data.properties.*.contract_number.required' => 'رقم عقد المزاد / الإسناد مطلوب لكل عقار.',
            'data.properties.*.contract_number.max' => 'رقم عقد المزاد / الإسناد يجب ألا يتجاوز 100 حرف.',
            'data.properties.*.ownership_share.max' => 'نسبة الملكية المعروضة يجب ألا تتجاوز 100%.',
            'data.properties.*.ownership_share.gt' => 'نسبة الملكية المعروضة يجب أن تكون أكبر من 0%.',
            'data.properties.*.area.gt' => 'المساحة يجب أن تكون أكبر من 0.',
            'data.properties.*.board_count.required' => 'عدد اللوحات مطلوب لكل عقار.',
            'data.properties.*.board_count.integer' => 'عدد اللوحات يجب أن يكون عدداً صحيحاً.',
            'data.properties.*.board_count.min' => 'عدد اللوحات يجب أن يكون 1 على الأقل.',
            'data.properties.*.board_dimensions.required' => 'مقاس اللوحات مطلوب لكل عقار.',
            'attachments.manager_certificate.required' => 'يجب إرفاق شهادة دورة مدير المزاد.',
            'attachments.auctioneer_certificate.required' => 'يجب إرفاق شهادة دورة محرّج المزاد.',
            'attachments.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'attachments.*.mimes' => 'يجب أن يكون الملف بصيغة: pdf, png, jpg, jpeg.',
            'attachments.*.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'attachments.*.*.mimes' => 'يجب أن يكون الملف بصيغة: pdf, png, jpg, jpeg.',
            'board_images_before.*.*.mimes' => 'يجب أن تكون صورة تركيب اللوحة بصيغة: png, jpg, jpeg, webp.',
            'board_images_before.*.*.max' => 'حجم صورة تركيب اللوحة يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
