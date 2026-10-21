<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Roles;

use App\Models\Access\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant') !== null; }
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_.-]*$/', Rule::unique('roles', 'name')->where('guard', 'tenant')->ignore($role)],
            'display_name' => ['required', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('guard', 'tenant')],
        ];
    }
}
