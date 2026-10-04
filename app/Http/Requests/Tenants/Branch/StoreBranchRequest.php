<?php

namespace App\Http\Requests\Tenants\Branch;

use App\Http\Requests\Concerns\WizardFormRequest;

class StoreBranchRequest extends WizardFormRequest
{
    /*
    |--------------------------------------------------------------------------
    | التحقق من الصلاحيات
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | قواعد التحقق
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'code'    => ['required', 'string', 'max:50', 'unique:branches,code', 'regex:/^[A-Z0-9]+$/'],
            'phone'   => ['nullable', 'string', 'between:7,15', 'regex:/^[0-9]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'city'    => ['nullable', 'string', 'max:100'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق المخصصة
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'name.required'    => 'اسم الفرع مطلوب',
            'name.string'      => 'اسم الفرع يجب أن يكون نصاً',
            'name.max'         => 'اسم الفرع يجب ألا يتجاوز 255 حرف',
            
            'code.required'    => 'كود الفرع مطلوب',
            'code.string'      => 'كود الفرع يجب أن يكون نصاً',
            'code.max'         => 'كود الفرع يجب ألا يتجاوز 50 حرف',
            'code.unique'      => 'كود الفرع مستخدم مسبقاً',
            'code.regex'       => 'كود الفرع يجب أن يحتوي على أحرف كبيرة وأرقام فقط',
            
            'phone.string'     => 'رقم الهاتف يجب أن يكون نصاً',
            'phone.between'    => 'رقم الهاتف الأرضي يجب أن يتكون من 7 إلى 15 رقماً',
            'phone.regex'      => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
            
            'address.string'   => 'العنوان يجب أن يكون نصاً',
            'address.max'      => 'العنوان يجب ألا يتجاوز 500 حرف',
            
            'city.string'      => 'المدينة يجب أن تكون نصاً',
            'city.max'         => 'اسم المدينة يجب ألا يتجاوز 100 حرف',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تنظيف البيانات قبل التحقق
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper($this->code), 
        ]);
    }
}
