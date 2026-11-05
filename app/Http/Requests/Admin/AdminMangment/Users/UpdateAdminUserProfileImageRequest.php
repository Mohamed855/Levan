<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminMangment\Users;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserProfileImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? auth('admin')->user() ?? auth()->user();
        $target = $this->route('user');
        $targetId = is_object($target) ? ($target->id ?? $target->getKey()) : $target;

        return $user instanceof AdminUser
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
