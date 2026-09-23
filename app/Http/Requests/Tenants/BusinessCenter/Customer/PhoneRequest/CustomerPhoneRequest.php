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
            'phone' => 'required|string|min:10|max:15|regex:/^[0-9+]+$/'
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
            'phone.min' => 'يجب أن يحتوي رقم الهاتف على 10 أرقام على الأقل',
            'phone.max' => 'يجب ألا يزيد رقم الهاتف عن 15 رقم',
            'phone.regex' => 'يجب أن يحتوي رقم الهاتف على أرقام فقط'
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
