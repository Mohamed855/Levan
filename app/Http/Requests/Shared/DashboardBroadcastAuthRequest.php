<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared;

class DashboardBroadcastAuthRequest extends DashboardNotificationRequest
{
    public function rules(): array
    {
        return ['channel_name' => ['required', 'string', 'max:255'], 'socket_id' => ['required', 'string', 'max:100']];
    }
}
