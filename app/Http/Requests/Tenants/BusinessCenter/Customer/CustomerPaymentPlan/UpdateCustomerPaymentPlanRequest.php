<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerPaymentPlan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPaymentPlanRequest extends FormRequest
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
            'due_date' => 'required|date|after_or_equal:today',
            'due_amount' => 'required|numeric|min:0.01|max:999999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.required' => 'تاريخ الاستحقاق مطلوب',
            'due_date.date' => 'تاريخ الاستحقاق يجب أن يكون تاريخ صحيح',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون اليوم أو بعده',

            'due_amount.required' => 'مبلغ الاستحقاق مطلوب',
            'due_amount.numeric' => 'مبلغ الاستحقاق يجب أن يكون رقم',
            'due_amount.min' => 'مبلغ الاستحقاق يجب أن يكون أكبر من صفر',
            'due_amount.max' => 'مبلغ الاستحقاق كبير جداً',
        ];
    }

    public function attributes(): array
    {
        return [
            'due_date' => 'تاريخ الاستحقاق',
            'due_amount' => 'مبلغ الاستحقاق',
        ];
    }
}
