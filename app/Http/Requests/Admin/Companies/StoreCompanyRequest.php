<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('companies.create') ?? false;
    }

    public function rules(): array
    {
        return [
            // Step 1: Company details
            'company_name'                  => ['required', 'string', 'max:255'],
            'commercial_number'             => ['required', 'string', 'digits:10'],
            'tax_number'                    => ['required', 'string', 'max:50'],
            'city_id'                       => ['required', 'exists:cities,id'],
            'address'                       => ['required', 'string', 'max:1000'],
            'email_notifications_enabled'   => ['nullable', 'boolean'],
            'two_factor_enabled'            => ['nullable', 'boolean'],
            'external_integrations_enabled' => ['nullable', 'boolean'],

            // Step 2: First Admin User
            'name'                          => ['required', 'string', 'min:2', 'max:255'],
            'phone'                         => ['required', 'string', 'regex:/^05[0-9]{8}$/'],
            'email'                         => ['required', 'email', 'max:255'],
            'password'                      => ['required', 'string', 'min:8', 'confirmed'],

            // Step 3: Subscription & Payment
            'plan_id'                       => ['required', 'exists:plans,id'],
            'start_date'                    => ['required', 'date'],
            'end_date'                      => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_users'                   => ['nullable', 'integer', 'min:1'],
            'payment_type'                  => ['required', 'in:paid,unpaid,trial'],
            'payment_method'                => ['nullable', 'required_if:payment_type,paid', 'in:bank_transfer,online,cash'],
            'receipt_upload'                => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('payment_type') === 'paid' && $this->input('payment_method') === 'bank_transfer'),
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:5120',
            ],
            'has_discount'                  => ['nullable', 'in:yes,no'],
            'discount_value'                => ['nullable', 'numeric', 'min:0'],
            'trial_end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
            'activate_immediately'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'        => 'يرجى إدخال اسم الشركة',
            'commercial_number.required'   => 'يرجى إدخال رقم السجل التجاري',
            'commercial_number.digits'     => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'tax_number.required'          => 'يرجى إدخال الرقم الضريبي',
            'city_id.required'             => 'يرجى اختيار المدينة',
            'address.required'             => 'يرجى إدخال العنوان',
            'name.required'                => 'يرجى إدخال اسم المسؤول',
            'phone.required'               => 'يرجى إدخال رقم الجوال',
            'phone.regex'                  => 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام',
            'email.required'               => 'يرجى إدخال البريد الإلكتروني',
            'email.email'                  => 'يرجى إدخال بريد إلكتروني صحيح',
            'password.required'            => 'يرجى إدخال كلمة المرور',
            'password.min'                 => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'           => 'تأكيد كلمة المرور غير متطابق',
            'plan_id.required'             => 'يرجى اختيار الباقة',
            'start_date.required'          => 'يرجى تحديد تاريخ البداية',
            'payment_type.required'        => 'يرجى تحديد حالة الدفع',
            'payment_method.required_if'   => 'يرجى اختيار وسيلة الدفع',
            'receipt_upload.required'      => 'يرجى رفع إيصال التحويل البنكي',
            'receipt_upload.mimes'         => 'ملف الإيصال يجب أن يكون صورة أو PDF',
            'receipt_upload.max'           => 'حجم ملف الإيصال يجب ألا يتجاوز 5 ميجابايت',
        ];
    }
}
