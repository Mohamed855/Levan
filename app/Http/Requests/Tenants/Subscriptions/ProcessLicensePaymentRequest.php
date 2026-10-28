<?php

namespace App\Http\Requests\Tenants\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class ProcessLicensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('subscriptions.add_licenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'new_licenses_count' => ['required', 'integer', 'between:1,1000'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_licenses_count.required' => 'عدد المستخدمين المطلوبين مفقود.',
            'new_licenses_count.between' => 'يجب أن يكون عدد المستخدمين بين 1 و1000.',
            'coupon_code.string' => 'رمز الخصم غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'new_licenses_count' => 'عدد المستخدمين الجدد',
            'coupon_code' => 'رمز الخصم',
        ];
    }
}
