<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Domains;

use Illuminate\Foundation\Http\FormRequest;

final class LinkManagedDomainRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('domains.manage') ?? false; }
    public function rules(): array { return ['domain' => ['required', 'string', 'max:253']]; }
}
