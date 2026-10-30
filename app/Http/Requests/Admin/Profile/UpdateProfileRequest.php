<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Profile;

use App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends AdminUserUpdateRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['password']);
        $rules['email'] = [
            'required', 'email:rfc', 'max:255',
            Rule::unique('mysql.admin_users', 'email')->ignore($this->user('admin')?->getKey()),
        ];

        return $rules;
    }
}
