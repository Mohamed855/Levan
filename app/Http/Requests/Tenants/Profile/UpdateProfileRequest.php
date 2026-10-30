<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Profile;

use App\Http\Requests\Tenants\UserManagement\User\UpdateUserRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends UpdateUserRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null && tenancy()->initialized;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['password'], $rules['role_id']);
        $rules['email'] = [
            'required', 'email', 'max:255',
            Rule::unique('tenant.users', 'email')->ignore($this->user('tenant')?->getKey()),
        ];

        return $rules;
    }
}
