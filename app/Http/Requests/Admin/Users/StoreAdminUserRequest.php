<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('admin_users.create') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('admin_users', 'email')], 'phone' => ['nullable', 'string', 'max:20'], 'password' => ['required', 'string', 'min:8', 'confirmed'], 'status' => ['required', 'in:active,inactive'], 'role_ids' => ['nullable', 'array'], 'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard', 'admin')]]; }
}
