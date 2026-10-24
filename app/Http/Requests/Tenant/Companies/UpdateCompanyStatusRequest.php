<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompanyStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('companies.edit') ?? false; }
    public function rules(): array { return ['status' => ['required', Rule::in(['active', 'inactive'])]]; }
}
