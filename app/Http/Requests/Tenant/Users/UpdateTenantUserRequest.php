<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Users;

use App\Models\Tenant\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('users.update') ?? false; }

    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($managedUser)],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard', 'tenant')],
        ];
    }
}
