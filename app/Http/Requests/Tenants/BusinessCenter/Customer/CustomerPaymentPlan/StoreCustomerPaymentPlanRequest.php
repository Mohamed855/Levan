<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerPaymentPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerPaymentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_plans' => 'required|array|min:1|max:50',
            'payment_plans.*.due_date' => 'required|date|after_or_equal:today',
            'payment_plans.*.due_amount' => 'required|numeric|min:0.01|max:999999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_plans.required' => 'يجب إضافة خطة سداد واحدة على الأقل',
            'payment_plans.array' => 'تنسيق خطط السداد غير صحيح',
            'payment_plans.min' => 'يجب إضافة خطة سداد واحدة على الأقل',
            'payment_plans.max' => 'لا يمكن إضافة أكثر من :max خطة سداد في المرة الواحدة',

            'payment_plans.*.due_date.required' => 'تاريخ الاستحقاق مطلوب',
            'payment_plans.*.due_date.date' => 'تاريخ الاستحقاق يجب أن يكون تاريخ صحيح',
            'payment_plans.*.due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون اليوم أو بعده',

            'payment_plans.*.due_amount.required' => 'مبلغ الاستحقاق مطلوب',
            'payment_plans.*.due_amount.numeric' => 'مبلغ الاستحقاق يجب أن يكون رقم',
            'payment_plans.*.due_amount.min' => 'مبلغ الاستحقاق يجب أن يكون أكبر من صفر',
            'payment_plans.*.due_amount.max' => 'مبلغ الاستحقاق كبير جداً',
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_plans' => 'خطط السداد',
            'payment_plans.*.due_date' => 'تاريخ الاستحقاق',
            'payment_plans.*.due_amount' => 'مبلغ الاستحقاق',
        ];
    }
}
