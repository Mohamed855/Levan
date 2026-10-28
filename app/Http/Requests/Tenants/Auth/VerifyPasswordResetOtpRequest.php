<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'الرجاء إدخال رمز التحقق.',
            'otp.string' => 'رمز التحقق يجب أن يكون نصاً أو أرقاماً.',
            'otp.size' => 'رمز التحقق يجب أن يتكون من 6 أرقام.',
        ];
    }

    public function attributes(): array
    {
        return [
            'otp' => 'رمز التحقق',
        ];
    }
}
