<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteCheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('checkout_verification');
    }

    public function rules(): array
    {
        $bankTransfer = $this->input('payment_method') === 'bank_transfer';

        return [
            'payment_method' => ['required', 'in:card,bank_transfer'],
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code'],
            'bank_id' => $bankTransfer ? ['required', 'exists:banks,id,is_active,1'] : ['nullable'],
            'transfer_receipt' => $bankTransfer ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'] : ['nullable'],
        ];
    }
}
