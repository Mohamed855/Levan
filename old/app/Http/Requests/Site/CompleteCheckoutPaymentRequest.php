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
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'bank_id' => $bankTransfer ? ['required', 'exists:banks,id,is_active,1'] : ['nullable'],
            'transfer_receipt' => $bankTransfer ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'] : ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'طريقة الدفع',
            'coupon_code' => 'كود الخصم',
            'bank_id' => 'الحساب البنكي',
            'transfer_receipt' => 'إيصال التحويل البنكي',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'يرجى اختيار طريقة الدفع للمتابعة.',
            'payment_method.in' => 'طريقة الدفع المحددة غير صالحة.',
            'bank_id.required' => 'يرجى اختيار الحساب البنكي المراد التحويل إليه.',
            'bank_id.exists' => 'الحساب البنكي المختار غير متاح حالياً.',
            'transfer_receipt.required' => 'يرجى إرفاق إيصال التحويل البنكي.',
            'transfer_receipt.file' => 'يجب رفع ملف إيصال صالح.',
            'transfer_receipt.mimes' => 'صيغة الملف غير مدعومة، يرجى رفع ملف بصيغة JPG أو PNG أو PDF.',
            'transfer_receipt.max' => 'حجم إيصال التحويل يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }
}
