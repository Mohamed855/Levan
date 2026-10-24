<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteCheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['card', 'bank_transfer'])],
            'bank_id' => ['nullable', 'required_if:payment_method,bank_transfer', 'integer', 'min:1'],
            'transfer_receipt' => ['nullable', 'required_if:payment_method,bank_transfer', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
