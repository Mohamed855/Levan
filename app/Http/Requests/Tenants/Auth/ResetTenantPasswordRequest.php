<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetTenantPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
