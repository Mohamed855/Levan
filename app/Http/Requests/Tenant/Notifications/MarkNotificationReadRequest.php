<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Notifications;

use Illuminate\Foundation\Http\FormRequest;

final class MarkNotificationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->hasPermissionTo('notifications.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
        ];
    }
}
