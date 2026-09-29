<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SendCustomerSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => 'required|string|max:20',
            'phone_type' => 'required|string|in:primary,mobile,home,work,other',
            'template_id' => 'required|exists:message_templates,id',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'رقم الهاتف مطلوب',
            'phone_number.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone_number.max' => 'رقم الهاتف طويل جداً',

            'phone_type.required' => 'نوع الهاتف مطلوب',
            'phone_type.string' => 'نوع الهاتف يجب أن يكون نص',
            'phone_type.in' => 'نوع الهاتف غير صحيح',

            'template_id.required' => 'قالب الرسالة مطلوب',
            'template_id.exists' => 'قالب الرسالة المحدد غير موجود',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone_number' => 'رقم الهاتف',
            'phone_type' => 'نوع الهاتف',
            'template_id' => 'قالب الرسالة',
        ];
    }
}
