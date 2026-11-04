<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminTwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hasLoginId = $this->session()->has('login.id');
        $guard = $this->session()->get('login.guard');

        return $hasLoginId && $guard === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && ! is_array($this->input('code')) && trim((string) $this->input('code')) === '') {
            $this->merge(['code' => null]);
        }
        if ($this->has('recovery_code') && ! is_array($this->input('recovery_code')) && trim((string) $this->input('recovery_code')) === '') {
            $this->merge(['recovery_code' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'digits:6', 'required_without:recovery_code', 'prohibits:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code', 'prohibits:code'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required_without' => 'الرجاء إدخال الرمز أو كود الاسترجاع.',
            'code.digits' => 'يجب أن يتكون الرمز من 6 أرقام.',
            'code.prohibits' => 'لا يمكن إرسال الرمز وكود الاسترجاع معاً.',
            'recovery_code.required_without' => 'الرجاء إدخال الرمز أو كود الاسترجاع.',
            'recovery_code.prohibits' => 'لا يمكن إرسال الرمز وكود الاسترجاع معاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'رمز المصادقة',
            'recovery_code' => 'كود الاسترجاع',
        ];
    }
}
