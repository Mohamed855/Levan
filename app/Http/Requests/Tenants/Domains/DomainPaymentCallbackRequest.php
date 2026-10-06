<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class DomainPaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null;
    }

    public function rules(): array
    {
        return [
            'attempt' => ['required', 'uuid'],
            'id' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'attempt' => 'معرّف عملية الدفع',
            'id' => 'معرّف عملية Moyasar',
        ];
    }
}
