<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\PhoneRequest;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPhoneRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Determine if User is Authorized
    |--------------------------------------------------------------------------
    | التحقق من صلاحية المستخدم لإرسال الطلب
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | قواعد التحقق من صحة البيانات المرسلة
    */
    public function rules(): array
    {
        return [
            'phone' => 'required|string|size:10|regex:/^05[0-9]{8}$/'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    | رسائل التحقق المخصصة باللغة العربية
    */
    public function messages(): array
    {
        return [
            'phone.required' => 'حقل رقم الهاتف مطلوب',
            'phone.string' => 'يجب أن يكون رقم الهاتف نصاً',
            'phone.size' => 'يجب أن يحتوي رقم الهاتف على 10 أرقام',
            'phone.regex' => 'يجب أن يبدأ رقم الهاتف بـ 05'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    | أسماء الحقول المخصصة باللغة العربية
    */
    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف'
        ];
    }
}
