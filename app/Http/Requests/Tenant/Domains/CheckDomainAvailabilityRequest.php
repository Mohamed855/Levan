<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Domains;

use Illuminate\Foundation\Http\FormRequest;

final class CheckDomainAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('domains.view') ?? false; }
    public function rules(): array { return ['domains' => ['required', 'array', 'min:1', 'max:10'], 'domains.*' => ['required', 'string', 'max:253']]; }
}
