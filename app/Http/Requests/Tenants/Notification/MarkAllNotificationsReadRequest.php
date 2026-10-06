<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Notification;

use Illuminate\Foundation\Http\FormRequest;

class MarkAllNotificationsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
