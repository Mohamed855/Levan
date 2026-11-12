<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Users;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangeCurrentUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        $user = $this->user('tenant');
        $rules = [
            'current_password' => ['required', 'current_password:tenant'],
            'new_password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ];

        if ($user?->must_change_password) {
            $rules['current_password'] = ['exclude'];
            $rules['new_password'][] = static function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                if (is_string($value) && Hash::check($value, $user->password)) {
                    $fail('يجب أن تختلف كلمة المرور الجديدة عن كلمة المرور الحالية.');
                }
            };
        }

        return $rules;
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
