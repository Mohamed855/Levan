<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmAdminTwoFactorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
