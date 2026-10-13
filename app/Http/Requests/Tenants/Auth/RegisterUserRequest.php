<?php

namespace App\Http\Requests\Tenants\Auth;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.size' => 'رقم الجوال يجب أن يتكون من 10 أرقام.',
            'phone.regex' => 'رقم الجوال يجب أن يبدأ بـ 05.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الجوال',
        ];
    }
}
