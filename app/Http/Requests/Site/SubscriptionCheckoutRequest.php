<?php

namespace App\Http\Requests\Site;

use App\Rules\SystemEmailAvailable;
use App\Rules\SystemPhoneAvailable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizeDigits = static function (?string $val): string {
            if ($val === null) {
                return '';
            }
            $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            return trim(str_replace($arabic, $western, $val));
        };

        $updates = [];
        if ($this->has('billing_phone')) {
            $updates['billing_phone'] = preg_replace('/\D+/', '', $normalizeDigits($this->input('billing_phone')));
        }
        if ($this->has('billing_commercial_number')) {
            $updates['billing_commercial_number'] = $normalizeDigits($this->input('billing_commercial_number'));
        }
        if ($this->has('billing_tax_number')) {
            $updates['billing_tax_number'] = $normalizeDigits($this->input('billing_tax_number'));
        }

        $this->merge($updates);
    }

    public function rules(): array
    {
        return [
            'billing_company_name' => ['required', 'string', 'min:2', 'max:255', Rule::unique('tenants', 'company_name')],
            'billing_commercial_number' => ['required', 'digits:10', 'regex:/^\d{10}$/', Rule::unique('tenants', 'commercial_number')],
            'billing_tax_number' => ['required', 'digits:15', 'regex:/^3\d{13}3$/'],
            'billing_city_id' => ['required', 'exists:cities,id'],
            'billing_address' => ['required', 'string', 'max:255'],
            'billing_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{Arabic}a-zA-Z\s\.\-]+$/u'],
            'billing_email' => ['required', 'email', 'max:255', new SystemEmailAvailable()],
            'billing_phone' => ['required', 'regex:/^(05\d{8}|966\d{9})$/', new SystemPhoneAvailable()],
            'billing_password' => ['required', 'string', 'min:8', 'max:50', 'confirmed'],
            'coupon_code' => ['nullable', 'string', 'max:255'],

            // حقول التحويل البنكي
        ];
    }

    public function messages(): array
    {
        return [
            'billing_company_name.required' => 'اسم الشركة مطلوب',
            'billing_company_name.unique' => 'اسم الشركة مستخدم مسبقًا',

            'billing_commercial_number.required' => 'رقم السجل التجاري مطلوب',
            'billing_commercial_number.digits' => 'يجب أن يتكون رقم السجل التجاري من 10 أرقام',
            'billing_commercial_number.unique' => 'رقم السجل التجاري مستخدم مسبقًا',
            'billing_commercial_number.regex' => 'يجب أن يحتوي رقم السجل التجاري على 10 أرقام فقط',

            'billing_tax_number.required' => 'الرقم الضريبي مطلوب',
            'billing_tax_number.digits' => 'يجب أن يحتوي الرقم الضريبي على 15 رقمًا',
            'billing_tax_number.regex' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا، يبدأ بالرقم 3 وينتهي بالرقم 3',

            'billing_city_id.required' => 'المدينة مطلوبة',
            'billing_city_id.exists' => 'المدينة المختارة غير صالحة',

            'billing_address.required' => 'العنوان مطلوب',
            'billing_address.string' => 'العنوان يجب أن يكون نصًا',
            'billing_address.max' => 'العنوان يجب أن لا يتجاوز 255 حرفًا',

            'billing_name.required' => 'اسم المستخدم مطلوب',
            'billing_name.regex' => 'اسم المستخدم يجب أن يحتوي على حروف عربية أو إنجليزية فقط',

            'billing_email.required' => 'البريد الإلكتروني مطلوب',
            'billing_email.email' => 'البريد الإلكتروني غير صحيح',
            'billing_email.unique' => 'البريد الإلكتروني موجود بالفعل',

            'billing_phone.required' => 'رقم الجوال مطلوب',
            'billing_phone.regex' => 'صيغة رقم الجوال غير صحيحة (يجب أن يبدأ بـ 05 أو 966)',

            'billing_password.required' => 'كلمة المرور مطلوبة',
            'billing_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'billing_password.confirmed' => 'تأكيد كلمة المرور غير متطابق',

            'payment_method.required' => 'يرجى اختيار طريقة الدفع',
            'payment_method.in' => 'طريقة الدفع المختارة غير مدعومة',

            'bank_id.required' => 'البنك مطلوب',
            'bank_id.exists' => 'البنك المختار غير صالح',

            'transfer_receipt.required' => 'يجب رفع إيصال التحويل ',
            'transfer_receipt.file' => 'يجب أن يكون كل إيصال تحويل ملفًا',
            'transfer_receipt.mimes' => 'يجب أن يكون نوع الإيصال jpg أو jpeg أو png أو pdf',
            'transfer_receipt.max' => 'يجب ألا يتجاوز حجم إيصال التحويل 5 ميغابايت',

            'coupon_code.exists' => 'كود الخصم غير صالح',
        ];
    }

    public function attributes(): array
    {
        return [
            'billing_company_name' => 'اسم الشركة أو المؤسسة',
            'billing_commercial_number' => 'رقم السجل التجاري',
            'billing_tax_number' => 'الرقم الضريبي',
            'billing_city_id' => 'المدينة',
            'billing_address' => 'العنوان',
            'billing_name' => 'اسم المدير المسؤول',
            'billing_email' => 'البريد الإلكتروني',
            'billing_phone' => 'رقم الجوال',
            'billing_password' => 'كلمة المرور',
        ];
    }

    public function failedValidation($validator): void
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withErrors($validator)
                ->withInput(
                    $this->except([
                        'billing_password',
                        'billing_password_confirmation',
                    ])
                )
        );
    }
}
