<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class RotateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->hasPermissionTo('settings.update') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['nullable', 'string', 'max:100']];
    }
}
