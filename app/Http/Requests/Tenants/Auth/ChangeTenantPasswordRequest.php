<?php

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangeTenantPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required'],
            'new_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => 'كلمة المرور الحالية', 'new_password' => 'كلمة المرور الجديدة', 'new_password_confirmation' => 'تأكيد كلمة المرور الجديدة'];
    }
}
