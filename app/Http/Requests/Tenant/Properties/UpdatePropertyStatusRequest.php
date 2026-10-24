<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Properties;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePropertyStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('properties.edit') ?? false; }
    public function rules(): array { return ['is_active' => ['required', 'boolean']]; }
}
