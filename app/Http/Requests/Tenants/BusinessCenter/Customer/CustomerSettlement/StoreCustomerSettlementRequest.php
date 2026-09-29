<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerSettlement;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settlements' => 'required|array|min:1|max:20',
            'settlements.*.settlement_date' => 'required|date',
            'settlements.*.settlement_amount' => 'required|numeric|min:0.01|max:999999999.99',
            'settlements.*.settlement_number' => 'required|string|max:100',
            'settlements.*.settlement_type' => 'required|in:cash_with_update,cash_without_update,purchase_debt_with_update,purchase_debt_without_update',
            'settlements.*.file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        ];
    }

    public function messages(): array
    {
        return [
            'settlements.required' => 'يجب إضافة تسوية واحدة على الأقل',
            'settlements.array' => 'تنسيق التسويات غير صحيح',
            'settlements.min' => 'يجب إضافة تسوية واحدة على الأقل',
            'settlements.max' => 'لا يمكن إضافة أكثر من :max تسوية في المرة الواحدة',

            'settlements.*.settlement_date.required' => 'تاريخ التسوية مطلوب',
            'settlements.*.settlement_date.date' => 'تاريخ التسوية يجب أن يكون تاريخ صحيح',

            'settlements.*.settlement_amount.required' => 'مبلغ التسوية مطلوب',
            'settlements.*.settlement_amount.numeric' => 'مبلغ التسوية يجب أن يكون رقم',
            'settlements.*.settlement_amount.min' => 'مبلغ التسوية يجب أن يكون أكبر من صفر',
            'settlements.*.settlement_amount.max' => 'مبلغ التسوية كبير جداً',

            'settlements.*.settlement_number.required' => 'رقم التسوية مطلوب',
            'settlements.*.settlement_number.string' => 'رقم التسوية يجب أن يكون نص',
            'settlements.*.settlement_number.max' => 'رقم التسوية لا يجب أن يتجاوز :max حرف',

            'settlements.*.settlement_type.required' => 'نوع التسوية مطلوب',
            'settlements.*.settlement_type.in' => 'نوع التسوية غير صحيح',

            'settlements.*.file.file' => 'يجب أن يكون المرفق ملف صحيح',
            'settlements.*.file.max' => 'حجم المرفق لا يجب أن يتجاوز :max كيلوبايت',
            'settlements.*.file.mimes' => 'نوع المرفق غير مدعوم. الأنواع المدعومة: PDF, DOC, XLS, صور',
        ];
    }

    public function attributes(): array
    {
        return [
            'settlements' => 'التسويات',
            'settlements.*.settlement_date' => 'تاريخ التسوية',
            'settlements.*.settlement_amount' => 'مبلغ التسوية',
            'settlements.*.settlement_number' => 'رقم التسوية',
            'settlements.*.settlement_type' => 'نوع التسوية',
            'settlements.*.file' => 'المرفق',
        ];
    }
}
