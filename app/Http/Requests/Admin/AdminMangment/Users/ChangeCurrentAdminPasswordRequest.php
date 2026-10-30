<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminMangment\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangeCurrentAdminPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:admin'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقتين.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'كلمة المرور الحالية',
            'new_password' => 'كلمة المرور الجديدة',
            'new_password_confirmation' => 'تأكيد كلمة المرور الجديدة',
        ];
    }
}
