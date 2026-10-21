<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdminRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('roles.update') ?? false; }
    public function rules(): array { $role = $this->route('role'); return ['name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_.-]*$/', Rule::unique('roles', 'name')->where('guard', 'admin')->ignore($role)], 'display_name' => ['required', 'string', 'max:255'], 'permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('guard', 'admin')]]; }
}
