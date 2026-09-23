<?php

namespace App\Http\Requests\Admin\AdminMangment\Role;


use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateUserRoleRequest extends FormRequest
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
            'role' => [
                'required',
                'exists:roles,id'
            ],

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
            'role.required' => 'الدور مطلوب',
            'role.exists' => 'الدور المحدد غير موجود',


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
            'role' => 'الدور',
        ];
    }
}
