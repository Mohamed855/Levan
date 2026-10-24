<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantUserStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('users.update') ?? false; }
    public function rules(): array { return ['status' => ['required', Rule::in(['active', 'inactive'])]]; }
}
