<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution;

use App\Http\Requests\Concerns\WizardFormRequest;

class CreateDistributionRequest extends WizardFormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        $rules = [
            'distribution_method' => 'required|in:balanced_amount,balanced_count,hybrid_balanced',
            'collector_ids' => 'required|array|min:1',
            'collector_ids.*' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'portfolio_id' => 'required|exists:portfolios,id',
            'apply_targets' => 'nullable|boolean',
            'calculator_enabled' => 'nullable|boolean',
            'calculator_monthly_target' => 'nullable|numeric|min:0',
            'calculator_employees_count' => 'nullable|integer|min:1',
            'calculator_duration' => 'nullable|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->boolean('apply_targets')) {
            $rules['calculator_monthly_target'] = 'required|numeric|min:1';
            $rules['calculator_employees_count'] = 'required|integer|min:1';
            $rules['calculator_duration'] = 'required|integer|min:1|max:12';
        }

        return $rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'distribution_method.required' => 'يجب اختيار طريقة التوزيع',
            'distribution_method.in' => 'طريقة التوزيع المحددة غير صالحة',
            'collector_ids.required' => 'يجب اختيار محصل واحد على الأقل',
            'collector_ids.array' => 'بيانات المحصلين غير صالحة',
            'collector_ids.min' => 'يجب اختيار محصل واحد على الأقل',
            'collector_ids.*.required' => 'بيانات المحصل مطلوبة',
            'collector_ids.*.exists' => 'أحد المحصلين المحددين غير موجود',
            'company_id.required' => 'يجب اختيار الشركة',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'portfolio_id.required' => 'يجب اختيار المحفظة',
            'portfolio_id.exists' => 'المحفظة المحددة غير موجودة',
            'apply_targets.boolean' => 'قيمة تطبيق الأهداف غير صالحة',
            'calculator_monthly_target.required' => 'الهدف الشهري مطلوب عند تفعيل الأهداف',
            'calculator_monthly_target.numeric' => 'الهدف الشهري يجب أن يكون رقم',
            'calculator_monthly_target.min' => 'الهدف الشهري يجب أن يكون أكبر من صفر',
            'calculator_employees_count.required' => 'عدد الموظفين مطلوب عند تفعيل الأهداف',
            'calculator_employees_count.integer' => 'عدد الموظفين يجب أن يكون رقم صحيح',
            'calculator_employees_count.min' => 'عدد الموظفين يجب أن يكون على الأقل 1',
            'calculator_duration.required' => 'المدة بالأشهر مطلوبة عند تفعيل الأهداف',
            'calculator_duration.integer' => 'المدة يجب أن تكون رقم صحيح',
            'calculator_duration.min' => 'المدة يجب أن تكون شهر واحد على الأقل',
            'calculator_duration.max' => 'المدة يجب ألا تتجاوز 12 شهر',
            'notes.string' => 'ملاحظات التوزيع يجب أن تكون نص',
            'notes.max' => 'ملاحظات التوزيع يجب ألا تتجاوز 1000 حرف',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Attributes
    |--------------------------------------------------------------------------
    */
    public function attributes(): array
    {
        return [
            'distribution_method' => 'طريقة التوزيع',
            'collector_ids' => 'المحصلين',
            'company_id' => 'الشركة',
            'portfolio_id' => 'المحفظة',
            'apply_targets' => 'تطبيق الأهداف',
            'calculator_monthly_target' => 'الهدف الشهري',
            'calculator_employees_count' => 'عدد الموظفين',
            'calculator_duration' => 'المدة',
            'notes' => 'الملاحظات',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('apply_targets')) {
                $employeesCount = intval($this->input('calculator_employees_count', 0));
                $collectorsCount = is_array($this->input('collector_ids')) 
                    ? count($this->input('collector_ids')) 
                    : 0;

                if ($employeesCount > 0 && $employeesCount !== $collectorsCount) {
                    $validator->errors()->add(
                        'collector_ids', 
                        "عدد المحصلين المختارين ({$collectorsCount}) يجب أن يطابق عدد الموظفين في الحاسبة ({$employeesCount})"
                    );
                }
            }
        });
    }
}
