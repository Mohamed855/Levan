<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyCheckoutOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('checkout_verification');
    }

    public function rules(): array
    {
        return ['otp' => ['required', 'digits:6']];
    }
}
