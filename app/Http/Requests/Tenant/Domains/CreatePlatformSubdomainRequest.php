<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Domains;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePlatformSubdomainRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('domains.manage') ?? false; }
    public function rules(): array
    {
        return ['label' => ['required', 'string', 'max:63'], 'control_domain_id' => ['required', 'integer', 'min:1']];
    }
}
