<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateTenantPasswordRequest extends FormRequest
{
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:tenant'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}
