<?php

namespace App\Http\Requests\Tenants\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'card_number'      => preg_replace('/\s+/', '', (string) $this->input('card_number')),
            'exp_date_display' => preg_replace('/\s+/', '', (string) $this->input('exp_date_display')),
        ]);
    }

    public function rules()
    {
        return [
            'card_number'               => ['required', 'string', 'regex:/^[0-9]{13,19}$/',],
            'card_name_display'         => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:100',],
            'cvc'                       => ['required', 'digits_between:3,4',],
            'exp_date_display'          => ['required', 'string', 'regex:/^\d{2}\/?\d{2}$/',],

            'coupon_code'               => ['nullable', 'string',],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $exp = $this->input('exp_date_display');
            if ($exp && preg_match('/^(\d{2})\/?(\d{2})$/', $exp, $m)) {
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

    public function messages()
    {
        return [
            'cardType.required'                     => 'نوع وسيلة الدفع مطلوب',
            'cardType.in'                           => 'وسيلة الدفع غير مدعومة',

            'card_number.required'                  => 'رقم البطاقة مطلوب',
            'card_number.regex'                     => 'صيغة رقم البطاقة غير صحيحة',

            'card_name_display.required'            => 'اسم حامل البطاقة مطلوب',
            'card_name_display.regex'               => 'الاسم على البطاقة يجب أن يكون بحروف إنجليزية فقط',

            'cvc.required'                          => 'رمز التحقق مطلوب',
            'cvc.digits_between'                    => 'رمز التحقق يجب أن يكون بين 3 و 4 أرقام',

            'exp_date_display.required'             => 'تاريخ انتهاء البطاقة مطلوب',
            'exp_date_display.regex'                => 'صيغة تاريخ الانتهاء يجب أن تكون MM/YY',

            'coupon_code.exists'                    => 'كود الخصم غير صالح',
        ];
    }


    public function failedValidation($validator)
    {
        $errorMessages = implode(', ', $validator->errors()->all());

        throw new HttpResponseException(
            redirect()->to(tenant_route_name('subscriptions.index'))->with('error', $errorMessages)->withInput()
        );
    }
}
