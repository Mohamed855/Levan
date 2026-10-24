<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Notifications;

use Illuminate\Foundation\Http\FormRequest;

final class MassDeleteNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->hasPermissionTo('notifications.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
