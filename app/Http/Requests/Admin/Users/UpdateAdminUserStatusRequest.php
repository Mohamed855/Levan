<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('admin_users.update') ?? false;
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
