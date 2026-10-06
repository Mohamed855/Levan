<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Settings;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('system.settings') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
