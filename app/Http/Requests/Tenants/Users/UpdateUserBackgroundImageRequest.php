<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Users;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserBackgroundImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');
        $target = $this->route('user');

        return $user instanceof User
            && $target instanceof User
            && ((int) $user->id === (int) $target->id || $user->can('users.edit'));
    }

    public function rules(): array
    {
        return ['background_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:4096']];
    }
}
