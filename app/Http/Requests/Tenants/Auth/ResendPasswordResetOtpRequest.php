<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
