<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangeCurrentUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:tenant'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقتين.',
        ];
    }
}
