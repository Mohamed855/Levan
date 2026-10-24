<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Bills;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitBankTransferReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('bills.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bank_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'receipt' => 'إشعار التحويل',
            'bank_id' => 'الحساب البنكي',
        ];
    }

    public function messages(): array
    {
        return [
            'receipt.required' => 'يرجى إرفاق إشعار أو إيصال التحويل.',
            'receipt.mimes' => 'يجب أن يكون الإشعار صورة (JPG, PNG) أو ملف PDF.',
            'receipt.max' => 'يجب ألا يتجاوز حجم الملف 5 ميجابايت.',
            'bank_id.required' => 'يرجى اختيار الحساب البنكي المحول إليه.',
        ];
    }
}
