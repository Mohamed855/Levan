<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\UserManagement\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagedUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
        ];
    }
}
