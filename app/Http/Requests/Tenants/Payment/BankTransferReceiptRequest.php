<?php

namespace App\Http\Requests\Tenants\Payment;

use Illuminate\Foundation\Http\FormRequest;

class BankTransferReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('bills.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'bank_id' => ['required', 'integer', 'exists:mysql.banks,id'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages()
    {
        return [
            'bank_id.required' => 'يجب اختيار البنك المحول إليه.',
            'bank_id.exists' => 'البنك المحدد غير صالح. يرجى اختيار بنك من القائمة.',

            'receipt.required' => 'يجب رفع صورة الإيصال.',
            'receipt.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'receipt.mimes' => 'صيغة الملف المسموح بها هي: jpeg, png, jpg, gif.',
            'receipt.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}
