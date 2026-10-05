<?php

namespace App\Http\Requests\Tenants\UserManagement\User;

use App\Http\Requests\Concerns\WizardFormRequest;

class StoreUserRequest extends WizardFormRequest
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
            'name'     => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[\pL\s\-\.]+$/u'
            ],
            'employee_number' => [
                'required',
                'string',
                'max:50',
                'min:1',
                'regex:/^[A-Za-z0-9]+$/',
                'unique:users,employee_number'
            ],
            'email'    => [
                'required',
                // 'email:rfc,dns',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],
            'phone'    => [
                'required',
                'string',
                'size:10',
                'regex:/^05[0-9]{8}$/'
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'image'    => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
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
            'name.required'       => 'الاسم مطلوب',
            'name.string'         => 'الاسم يجب أن يكون نص',
            'name.max'           => 'الاسم يجب ألا يزيد عن 255 حرف',
            'name.min'           => 'الاسم يجب ألا يقل عن حرفين',
            'name.regex'         => 'الاسم يجب أن يحتوي على أحرف فقط',

            'employee_number.required' => 'الرقم الوظيفي مطلوب',
            'employee_number.string'   => 'الرقم الوظيفي يجب أن يكون نص',
            'employee_number.max'      => 'الرقم الوظيفي يجب ألا يزيد عن 50 حرف',
            'employee_number.min'      => 'الرقم الوظيفي يجب ألا يقل عن حرف واحد',
            'employee_number.regex'    => 'الرقم الوظيفي يجب أن يحتوي على أحرف إنجليزية وأرقام فقط',
            'employee_number.unique'   => 'الرقم الوظيفي مستخدم مسبقاً',

            'email.required'      => 'البريد الإلكتروني مطلوب',
            'email.email'         => 'البريد الإلكتروني غير صحيح',
            'email.max'          => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرف',
            'email.unique'       => 'البريد الإلكتروني مستخدم مسبقاً',

            'password.required'   => 'كلمة المرور مطلوبة',
            'password.string'     => 'كلمة المرور يجب أن تكون نص',
            'password.min'       => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',

            'phone.required'      => 'رقم الهاتف مطلوب',
            'phone.string'        => 'رقم الهاتف يجب أن يكون نص',
            'phone.size'         => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex'        => 'رقم الهاتف يجب أن يبدأ بـ 05',

            'role_id.required' => 'يجب اختيار الدور',
            'role_id.integer'  => 'معرف الدور يجب أن يكون رقماً',
            'role_id.exists'   => 'الدور المحدد غير موجود',


            'image.image'         => 'الملف يجب أن يكون صورة',
            'image.mimes'        => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'image.max'          => 'حجم الصورة يجب ألا يزيد عن 2 ميجابايت',
            'image.dimensions'   => 'أبعاد الصورة يجب أن تكون بين 100x100 و 2000x2000 بكسل'
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
            'name'                 => 'الاسم',
            'employee_number'      => 'الرقم الوظيفي',
            'email'                => 'البريد الإلكتروني',
            'password'             => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'phone'                => 'رقم الهاتف',
            'image'                => 'الصورة الشخصية',
            'role_id'              => 'الدور',

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
            'employee_number' => strtoupper(trim($this->employee_number)),
            'email' => strtolower(trim($this->email)),
            'phone' => trim((string) $this->phone),
        ]);
    }
}
