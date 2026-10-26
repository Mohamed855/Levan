<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class DashboardNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user($this->route('dashboard_scope') === 'admin' ? 'admin' : 'tenant');
        $status = $user?->status;

        return $user && ($status instanceof \BackedEnum ? $status->value : $status) === 'active';
    }

    public function rules(): array
    {
        return [];
    }
}
