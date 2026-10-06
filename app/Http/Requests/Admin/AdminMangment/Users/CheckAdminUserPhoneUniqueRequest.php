<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminMangment\Users;

use Illuminate\Foundation\Http\FormRequest;

class CheckAdminUserPhoneUniqueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'], 'user_id' => ['nullable', 'integer']];
    }
}
