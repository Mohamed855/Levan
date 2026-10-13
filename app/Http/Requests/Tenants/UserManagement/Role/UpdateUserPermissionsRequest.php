<?php

namespace App\Http\Requests\Tenants\UserManagement\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionsRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('roles.assign') ?? false;
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
            'permissions' => [
                'array',
            ],
            'permissions.*' => [
                'string',
                'exists:permissions,name',
            ],
            'denied_permissions' => [
                'array',
            ],
            'denied_permissions.*' => [
                'string',
                'exists:permissions,name',
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
            'permissions.array' => 'الصلاحيات يجب أن تكون مصفوفة',
            'permissions.*.string' => 'كل صلاحية يجب أن تكون نص',
            'permissions.*.exists' => 'الصلاحية المحددة غير موجودة',

            'denied_permissions.array' => 'الصلاحيات المنزوعة يجب أن تكون مصفوفة',
            'denied_permissions.*.string' => 'كل صلاحية منزوعة يجب أن تكون نص',
            'denied_permissions.*.exists' => 'الصلاحية المنزوعة المحددة غير موجودة',
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
            'permissions' => 'الصلاحيات',
            'denied_permissions' => 'الصلاحيات المنزوعة',
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
            'permissions' => $this->permissions ?? [],
            'denied_permissions' => $this->denied_permissions ?? [],
        ]);
    }
}
