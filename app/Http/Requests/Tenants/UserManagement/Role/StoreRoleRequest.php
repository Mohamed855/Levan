<?php

namespace App\Http\Requests\Tenants\UserManagement\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request.
    */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'unique:roles,name',
                'regex:/^[\pL\s\-\.]+$/u'
            ],
            'permissions' => [
                'required_if:select_all,0',
                'array'
            ],
            'permissions.*' => [
                'exists:permissions,name'
            ],
            'select_all' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    | Get custom validation messages for the defined validation rules.
    */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدور مطلوب',
            'name.string' => 'اسم الدور يجب أن يكون نص',
            'name.max' => 'اسم الدور يجب ألا يزيد عن 255 حرف',
            'name.min' => 'اسم الدور يجب ألا يقل عن حرفين',
            'name.unique' => 'اسم الدور مستخدم مسبقاً',
            'name.regex' => 'اسم الدور يجب أن يحتوي على أحرف فقط',

            'permissions.required_if' => 'يجب اختيار صلاحية واحدة على الأقل',
            'permissions.array' => 'الصلاحيات يجب أن تكون مصفوفة',
            'permissions.*.exists' => 'الصلاحية المحددة غير موجودة',

            'select_all.boolean' => 'قيمة تحديد الكل يجب أن تكون صحيحة أو خاطئة'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Attributes
    |--------------------------------------------------------------------------
    | Get custom attributes for validator errors.
    */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الدور',
            'permissions' => 'الصلاحيات',
            'select_all' => 'تحديد الكل'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Data for Validation
    |--------------------------------------------------------------------------
    | Prepare the data for validation.
    */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'select_all' => $this->has('select_all') ? (bool) $this->select_all : false,
        ]);
    }
}
