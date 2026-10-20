<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminMangment\Users;

use Illuminate\Foundation\Http\FormRequest;

class CheckAdminUserEmailUniqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('users.create') || $this->user('admin')?->can('users.edit');
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'user_id' => ['nullable', 'integer']];
    }
}
