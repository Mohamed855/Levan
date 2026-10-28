<?php

namespace App\Http\Requests\Tenants\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class LicenseCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('subscriptions.add_licenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'new_licenses_count' => ['required', 'integer', 'between:1,1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_licenses_count.required' => 'يرجى تحديد عدد المستخدمين المطلوبين.',
            'new_licenses_count.between' => 'يجب أن يكون عدد المستخدمين بين 1 و1000.',
        ];
    }
}
