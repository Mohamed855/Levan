<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'رمز التحقق',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
        ];
    }
}
