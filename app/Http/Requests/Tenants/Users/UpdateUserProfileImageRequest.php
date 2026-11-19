<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Users;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\UserMangment\User\User;

class UpdateUserProfileImageRequest extends WizardFormRequest
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
        $isRemoving = $this->boolean('remove_profile_image') || $this->input('remove_profile_image') === '1';

        return [
            'profile_image' => [$isRemoving ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            'remove_profile_image' => ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return ['profile_image' => 'اختر صورة شخصية جديدة'];
    }
}
