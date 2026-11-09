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
