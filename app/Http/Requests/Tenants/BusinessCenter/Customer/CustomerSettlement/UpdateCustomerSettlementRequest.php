<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerSettlement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.edit')
            || $this->user('tenant')?->can('customers.edit_all')
            || false;
    }

    public function rules(): array
    {
        return [
            'settlement_date' => 'required|date',
            'settlement_amount' => 'required|numeric|min:0.01|max:999999999.99',
            'settlement_number' => 'required|string|max:100',
            'settlement_type' => 'required|in:cash_with_update,cash_without_update,purchase_debt_with_update,purchase_debt_without_update',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        ];
    }

    public function messages(): array
    {
        return [
            'settlement_date.required' => 'تاريخ التسوية مطلوب',
            'settlement_date.date' => 'تاريخ التسوية يجب أن يكون تاريخ صحيح',

            'settlement_amount.required' => 'مبلغ التسوية مطلوب',
            'settlement_amount.numeric' => 'مبلغ التسوية يجب أن يكون رقم',
            'settlement_amount.min' => 'مبلغ التسوية يجب أن يكون أكبر من صفر',
            'settlement_amount.max' => 'مبلغ التسوية كبير جداً',

            'settlement_number.required' => 'رقم التسوية مطلوب',
            'settlement_number.string' => 'رقم التسوية يجب أن يكون نص',
            'settlement_number.max' => 'رقم التسوية لا يجب أن يتجاوز :max حرف',

            'settlement_type.required' => 'نوع التسوية مطلوب',
            'settlement_type.in' => 'نوع التسوية غير صحيح',

            'file.file' => 'يجب أن يكون المرفق ملف صحيح',
            'file.max' => 'حجم المرفق لا يجب أن يتجاوز :max كيلوبايت',
            'file.mimes' => 'نوع المرفق غير مدعوم. الأنواع المدعومة: PDF, DOC, XLS, صور',
        ];
    }

    public function attributes(): array
    {
        return [
            'settlement_date' => 'تاريخ التسوية',
            'settlement_amount' => 'مبلغ التسوية',
            'settlement_number' => 'رقم التسوية',
            'settlement_type' => 'نوع التسوية',
            'file' => 'المرفق',
        ];
    }
}
