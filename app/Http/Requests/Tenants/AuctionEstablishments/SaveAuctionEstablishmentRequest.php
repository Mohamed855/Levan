<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\AuctionEstablishments;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
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
            if (isset($data['manager']['mobile'])) {
                $data['manager']['mobile'] = preg_replace('/\D+/', '', (string) $data['manager']['mobile']);
            }
            if (isset($data['manager']['identity_number'])) {
                $data['manager']['identity_number'] = preg_replace('/\D+/', '', (string) $data['manager']['identity_number']);
            }
            if (isset($data['auctioneer']['mobile'])) {
                $data['auctioneer']['mobile'] = preg_replace('/\D+/', '', (string) $data['auctioneer']['mobile']);
            }
            if (isset($data['auctioneer']['identity_number'])) {
                $data['auctioneer']['identity_number'] = preg_replace('/\D+/', '', (string) $data['auctioneer']['identity_number']);
            }
            if (isset($data['organization']['unified_number'])) {
                $data['organization']['unified_number'] = preg_replace('/\D+/', '', (string) $data['organization']['unified_number']);
            }
            if (isset($data['organization']['commercial_registration'])) {
                $data['organization']['commercial_registration'] = preg_replace('/\D+/', '', (string) $data['organization']['commercial_registration']);
            }
            if (isset($data['organization']['fal_license'])) {
                $data['organization']['fal_license'] = preg_replace('/\D+/', '', (string) $data['organization']['fal_license']);
            }
            if (isset($data['properties']) && is_array($data['properties'])) {
                foreach ($data['properties'] as $idx => $prop) {
                    if (is_array($prop)) {
                        foreach (['area', 'opening_value', 'entry_amount', 'estimated_value', 'ownership_share'] as $numKey) {
                            if (isset($prop[$numKey]) && is_string($prop[$numKey])) {
                                $data['properties'][$idx][$numKey] = str_replace(',', '', $prop[$numKey]);
                            }
                        }
                        if (isset($prop['deed_number'])) {
                            $data['properties'][$idx]['deed_number'] = preg_replace('/\D+/', '', (string) $prop['deed_number']);
                        }
                        if (isset($prop['agency_number'])) {
                            $data['properties'][$idx]['agency_number'] = preg_replace('/\D+/', '', (string) $prop['agency_number']);
                        }
                        if (isset($prop['contract_number'])) {
                            $data['properties'][$idx]['contract_number'] = preg_replace('/\D+/', '', (string) $prop['contract_number']);
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

    public function rules(): array
    {
        $rules = app(AuctionEstablishmentRequestData::class)->rules();
        foreach (['approval_number', 'approved_at', 'approved_by', 'created_by', 'updated_by', 'submitted_at'] as $field) {
            $rules[$field] = ['prohibited'];
            $rules['data.'.$field] = ['prohibited'];
        }
        $rules['data.attachments'] = ['prohibited'];
        $rules['attachments'] = ['nullable', 'array:manager_certificate,auctioneer_certificate,deed,valuation,representative_authority'];
        foreach (['manager_certificate', 'auctioneer_certificate'] as $type) {
            $rules['attachments.'.$type] = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'];
        }
        foreach (['deed', 'valuation', 'representative_authority'] as $type) {
            $rules['attachments.'.$type] = ['nullable', 'array'];
            $rules['attachments.'.$type.'.*'] = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'];
        }
        $rules['declaration_accepted'] = ['accepted'];

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $rights = $this->input('data.disclosures.disposition_rights', []);
            if (is_array($rights) && in_array('none', $rights, true) && count($rights) > 1) {
                $validator->errors()->add('data.disclosures.disposition_rights', 'لا يمكن جمع عدم وجود حقوق مع حقوق أخرى.');
            }
            if (is_array($rights) && in_array('other', $rights, true) && blank($this->input('data.disclosures.disposition_right_other'))) {
                $validator->errors()->add('data.disclosures.disposition_right_other', 'أدخل تفاصيل حقوق التصرف الأخرى.');
            }
        }];
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
            'data.properties.*.agency_number.regex' => 'رقم الوكالة يجب أن يحتوي على أرقام فقط.',
            'data.properties.*.contract_number.regex' => 'رقم عقد المزاد يجب أن يحتوي على أرقام فقط.',
            'data.properties.*.ownership_share.max' => 'نسبة الملكية المعروضة يجب ألا تتجاوز 100%.',
            'data.properties.*.ownership_share.gt' => 'نسبة الملكية المعروضة يجب أن تكون أكبر من 0%.',
            'data.properties.*.area.gt' => 'المساحة يجب أن تكون أكبر من 0.',
            'attachments.manager_certificate.required' => 'يجب إرفاق شهادة دورة مدير المزاد.',
            'attachments.auctioneer_certificate.required' => 'يجب إرفاق شهادة دورة محرّج المزاد.',
            'attachments.deed.*.required' => 'يجب إرفاق وثيقة الملكية لكل عقار.',
            'attachments.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'attachments.*.mimes' => 'يجب أن يكون الملف بصيغة: pdf, png, jpg, jpeg.',
            'attachments.*.*.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'attachments.*.*.mimes' => 'يجب أن يكون الملف بصيغة: pdf, png, jpg, jpeg.',
        ];
    }
}
