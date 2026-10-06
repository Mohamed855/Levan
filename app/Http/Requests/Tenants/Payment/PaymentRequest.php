<?php

namespace App\Http\Requests\Tenants\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'coupon_code'               => ['nullable', 'string',],
        ];
    }

    public function messages()
    {
        return [
            'coupon_code.string' => 'رمز الخصم غير صالح.',
        ];
    }
}
