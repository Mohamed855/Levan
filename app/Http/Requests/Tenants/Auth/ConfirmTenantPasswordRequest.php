<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTenantPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string']];
    }

    public function messages(): array
    {
        return ['password.required' => 'كلمة المرور مطلوبة.'];
    }

    public function attributes(): array
    {
        return ['password' => 'كلمة المرور'];
    }
}
