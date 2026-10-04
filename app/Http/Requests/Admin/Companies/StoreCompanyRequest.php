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
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_number' => ['required', 'string', 'digits:10'],
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{14}$/'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'external_integrations_enabled' => ['nullable', 'boolean'],

            // Step 2: First Admin User
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s\-\.]+$/u'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Step 3: Subscription & Payment
            'plan_id' => ['required', 'exists:plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_users' => ['nullable', 'integer', 'min:1'],
            'payment_type' => ['required', 'in:paid,unpaid,trial'],
            'payment_method' => ['nullable', 'required_if:payment_type,paid', 'in:bank_transfer,online,cash'],
            'receipt_upload' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('payment_type') === 'paid' && $this->input('payment_method') === 'bank_transfer'),
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:5120',
            ],
            'has_discount' => ['nullable', 'in:yes,no'],
            'discount_value' => ['nullable', Rule::requiredIf(fn () => $this->input('has_discount') === 'yes'), 'numeric', 'min:0'],
            'trial_end_date' => ['nullable', Rule::requiredIf(fn () => $this->input('payment_type') === 'trial'), 'date', 'after_or_equal:start_date'],
            'activate_immediately' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'يرجى إدخال اسم الشركة',
            'commercial_number.required' => 'يرجى إدخال رقم السجل التجاري',
            'commercial_number.digits' => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'tax_number.required' => 'يرجى إدخال الرقم الضريبي',
            'city_id.required' => 'يرجى اختيار المدينة',
            'address.required' => 'يرجى إدخال العنوان',
            'name.required' => 'يرجى إدخال اسم المسؤول',
            'name.string' => 'اسم المسؤول يجب أن يكون نصاً',
            'name.min' => 'اسم المسؤول يجب ألا يقل عن حرفين',
            'name.max' => 'اسم المسؤول يجب ألا يزيد عن 255 حرفاً',
            'name.regex' => 'اسم المسؤول يجب أن يحتوي على أحرف فقط',
            'phone.required' => 'يرجى إدخال رقم الجوال',
            'phone.size' => 'يجب أن يتكون رقم الجوال من 10 أرقام',
            'phone.regex' => 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'email.max' => 'يجب ألا يزيد البريد الإلكتروني عن 255 حرفاً',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'plan_id.required' => 'يرجى اختيار الباقة',
            'start_date.required' => 'يرجى تحديد تاريخ البداية',
            'payment_type.required' => 'يرجى تحديد حالة الدفع',
            'payment_method.required_if' => 'يرجى اختيار وسيلة الدفع',
            'receipt_upload.required' => 'يرجى رفع إيصال التحويل البنكي',
            'receipt_upload.file' => 'يجب أن يكون الإيصال ملفاً صالحاً',
            'receipt_upload.mimes' => 'ملف الإيصال يجب أن يكون صورة أو PDF',
            'receipt_upload.max' => 'حجم ملف الإيصال يجب ألا يتجاوز 5 ميجابايت',
            'discount_value.required' => 'يرجى إدخال قيمة الخصم أو التسوية',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'discount_value.min' => 'قيمة الخصم لا يمكن أن تكون سالبة',
            'trial_end_date.required' => 'يرجى تحديد تاريخ انتهاء الفترة التجريبية',
            'trial_end_date.after_or_equal' => 'تاريخ نهاية التجربة يجب أن يكون بعد تاريخ البداية أو مساوياً له',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'اسم الشركة',
            'commercial_number' => 'رقم السجل التجاري',
            'tax_number' => 'الرقم الضريبي',
            'city_id' => 'المدينة',
            'address' => 'العنوان',
            'name' => 'اسم المسؤول',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'plan_id' => 'الباقة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'total_users' => 'إجمالي المستخدمين',
            'payment_type' => 'حالة الدفع',
            'payment_method' => 'وسيلة الدفع',
            'receipt_upload' => 'إيصال التحويل البنكي',
            'has_discount' => 'الخصم أو التسوية',
            'discount_value' => 'قيمة الخصم',
            'trial_end_date' => 'تاريخ نهاية التجربة',
        ];
    }
}
