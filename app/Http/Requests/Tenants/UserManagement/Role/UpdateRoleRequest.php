<?php

namespace App\Http\Requests\Tenants\UserManagement\Role;

use App\Services\Tenants\UserManagement\Role\RoleService;
use App\Support\AccessLabels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('roles.edit') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request.
    */
    public function rules(): array
    {
        $role = $this->route('role');
        if ($role instanceof Role && RoleService::isProtectedRole($role)) {
            return [];
        }

        $roleId = is_object($role) ? ($role->id ?? $role->getKey()) : ($role ?? $this->input('role_id'));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('roles', 'name')->where('guard_name', 'tenant')->ignore($roleId),
                'regex:/^[A-Za-z][A-Za-z0-9_-]*$/',
            ],
            'permissions' => [
                'required_unless:select_all,true',
                'array',
                'min:1',
            ],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'tenant'),
            ],
            'select_all' => [
                'sometimes',
                'boolean',
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
            'name.required' => 'اسم الدور مطلوب',
            'name.string' => 'اسم الدور يجب أن يكون نص',
            'name.max' => 'اسم الدور يجب ألا يزيد عن 255 حرف',
            'name.min' => 'اسم الدور يجب ألا يقل عن حرفين',
            'name.unique' => 'اسم الدور مستخدم مسبقاً',
            'name.regex' => 'معرّف الدور يبدأ بحرف إنجليزي ويحتوي على أحرف وأرقام وشرطة أو شرطة سفلية فقط',

            'permissions.required' => 'يجب اختيار صلاحية واحدة على الأقل',
            'permissions.required_unless' => 'يجب اختيار صلاحية واحدة على الأقل',
            'permissions.min' => 'يجب اختيار صلاحية واحدة على الأقل',
            'permissions.array' => 'الصلاحيات يجب أن تكون مصفوفة',
            'permissions.*.string' => 'الصلاحية المحددة غير صالحة',
            'permissions.*.exists' => 'الصلاحية المحددة غير موجودة',

            'select_all.boolean' => 'قيمة تحديد الكل يجب أن تكون صحيحة أو خاطئة',
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
            'permissions' => 'الصلاحيات المتاحة',
            'permissions.*' => 'الصلاحيات المتاحة',
            'select_all' => 'تحديد الكل',
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
        $selectAllRaw = $this->input('select_all');
        $selectAll = filter_var($selectAllRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $this->merge([
            'name' => AccessLabels::roleKey($this->input('name'), is_object($this->route('role')) ? $this->route('role')->name : null),
            'select_all' => $selectAll ?? false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Validated Data
    |--------------------------------------------------------------------------
    | Get the validated data with additional processing.
    */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        // تحديد الصلاحيات بناءً على select_all
        if ($validated['select_all']) {
            $validated['permissions'] = Permission::query()
                ->where('guard_name', 'tenant')
                ->pluck('name')
                ->toArray();
        } else {
            $validated['permissions'] = $validated['permissions'] ?? [];
        }

        return $validated;
    }
}
