<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminTwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('login.id');
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required_without' => 'الرجاء إدخال الكود أو كود الاسترجاع.',
            'recovery_code.required_without' => 'الرجاء إدخال الكود أو كود الاسترجاع.',
        ];
    }
}
