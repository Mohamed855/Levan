<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('admin_users.update') ?? false; }
    public function rules(): array { $user = $this->route('user'); return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('admin_users', 'email')->ignore($user)], 'phone' => ['nullable', 'string', 'max:20'], 'password' => ['nullable', 'string', 'min:8', 'confirmed'], 'status' => ['required', 'in:active,inactive'], 'role_ids' => ['required', 'array', 'min:1'], 'role_ids.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard', 'admin')]]; }
}
