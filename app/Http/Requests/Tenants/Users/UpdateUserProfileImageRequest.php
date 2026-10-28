<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Users;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');
        $target = $this->route('user');
        $targetId = is_object($target) ? ($target->id ?? $target->getKey()) : $target;

        return $user instanceof User
            && $targetId !== null
            && ((int) $user->id === (int) $targetId || $user->can('users.edit'));
    }

    public function rules(): array
    {
        return ['profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240']];
    }
}
