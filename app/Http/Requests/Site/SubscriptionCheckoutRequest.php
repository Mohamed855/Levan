<?php

namespace App\Http\Requests\Site;

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
        $this->merge([
            'card_number'      => preg_replace('/\s+/', '', (string) $this->input('card_number')),
            'exp_date_display' => preg_replace('/\s+/', '', (string) $this->input('exp_date_display')),
            'billing_phone'    => preg_replace('/\D+/', '', (string) $this->input('billing_phone')),
        ]);
    }

    public function rules(): array
    {
        $isCard         = $this->input('payment_method') === 'card';
        $isBankTransfer = $this->input('payment_method') === 'bank_transfer';


        return [
            'billing_company_name'      => ['required', 'string', 'min:2', 'max:255', Rule::unique('tenants', 'company_name')],
            'billing_commercial_number' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9]+$/', Rule::unique('tenants', 'commercial_number')],
            'billing_name'              => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{Arabic}a-zA-Z\s\.\-]+$/u'],
            'billing_email'             => ['required', 'email', 'max:255'],
            'billing_phone'             => ['required', 'regex:/^(05\d{8}|966\d{9})$/'],
            'billing_password'          => ['required', 'string', 'min:8', 'max:50', 'confirmed'],
            'payment_method'            => ['required', 'in:card,bank_transfer'],

            'coupon_code'               => ['nullable', 'string', 'exists:coupons,code'],

            //  حقول البطاقة
            'card_number'               => $isCard ? ['required', 'string', 'regex:/^[0-9]{13,19}$/'] : ['nullable'],
            'card_name_display'         => $isCard ? ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:100'] : ['nullable'],
            'cvc'                       => $isCard ? ['required', 'digits_between:3,4'] : ['nullable'],
            'exp_date_display'          => $isCard ? ['required', 'string', 'regex:/^\d{2}\/?\d{2}$/'] : ['nullable'],

            // حقول التحويل البنكي
            'bank_id'                   => $isBankTransfer ? ['required', 'exists:banks,id'] : ['nullable'],
            'transfer_receipt'          => $isBankTransfer ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'] : ['nullable'],

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->input('payment_method') !== 'card') {
                return;
            }

            $exp = $this->input('exp_date_display');
            if ($exp && preg_match('/^(\d{2})\/?(\d{2})$/', $exp, $m)) {  //  أُزيلت المسافة
                $mm = (int) $m[1];
                $yy = (int) $m[2];

                if ($mm < 1 || $mm > 12) {
                    $v->errors()->add('exp_date_display', 'شهر انتهاء البطاقة غير صحيح.');
                    return;
                }

                $nowY = (int) now()->format('y');
                $nowM = (int) now()->format('m');

                if ($yy < $nowY || ($yy === $nowY && $mm < $nowM)) {
                    $v->errors()->add('exp_date_display', 'تاريخ انتهاء البطاقة يجب أن يكون مستقبليًا.');
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'billing_company_name.required'      => 'اسم الشركة مطلوب',
            'billing_company_name.unique'        => 'اسم الشركة مستخدم مسبقًا',

            'billing_commercial_number.required' => 'رقم السجل التجاري مطلوب',
            'billing_commercial_number.unique'   => 'رقم السجل التجاري مستخدم مسبقًا',
            'billing_commercial_number.regex'    => 'يجب أن يحتوي رقم السجل التجاري على أرقام فقط',

            'billing_name.required'              => 'اسم المستخدم مطلوب',
            'billing_name.regex'                 => 'اسم المستخدم يجب أن يحتوي على حروف عربية أو إنجليزية فقط',

            'billing_email.required'             => 'البريد الإلكتروني مطلوب',
            'billing_email.email'                => 'البريد الإلكتروني غير صحيح',

            'billing_phone.required'             => 'رقم الجوال مطلوب',
            'billing_phone.regex'                => 'صيغة رقم الجوال غير صحيحة (يجب أن يبدأ بـ 05 أو 966)',

            'billing_password.required'          => 'كلمة المرور مطلوبة',
            'billing_password.min'               => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'billing_password.confirmed'         => 'تأكيد كلمة المرور غير متطابق',

            'payment_method.required'            => 'يرجى اختيار طريقة الدفع',
            'payment_method.in'                  => 'طريقة الدفع المختارة غير مدعومة',

            'card_number.required'               => 'رقم البطاقة مطلوب',
            'card_number.regex'                  => 'صيغة رقم البطاقة غير صحيحة',

            'card_name_display.required'         => 'اسم حامل البطاقة مطلوب',
            'card_name_display.regex'            => 'الاسم على البطاقة يجب أن يكون بحروف إنجليزية فقط',

            'cvc.required'                       => 'رمز التحقق مطلوب',
            'cvc.digits_between'                 => 'رمز التحقق يجب أن يكون بين 3 و 4 أرقام',

            'exp_date_display.required'          => 'تاريخ انتهاء البطاقة مطلوب',
            'exp_date_display.regex'             => 'صيغة تاريخ الانتهاء يجب أن تكون MM/YY',


            'bank_id.required'                   => 'البنك مطلوب',
            'bank_id.exists'                     => 'البنك المختار غير صالح',

            'transfer_receipt.required'         => 'يجب رفع إيصال التحويل ',
            'transfer_receipt.file'             => 'يجب أن يكون كل إيصال تحويل ملفًا',
            'transfer_receipt.mimes'            => 'يجب أن يكون نوع الإيصال jpg أو jpeg أو png أو pdf',
            'transfer_receipt.max'              => 'يجب ألا يتجاوز حجم إيصال التحويل 5 ميغابايت',

            'coupon_code.exists'                 => 'كود الخصم غير صالح',
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
                        'card_number',
                        'cvc',
                        'exp_date_display',
                    ])
                )
        );
    }
}
