<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTenantTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string']];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'الرجاء إدخال كود التحقق.',
            'code.string' => 'كود التحقق يجب أن يكون نصًا.',
        ];
    }
}
