<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Import;

use Illuminate\Foundation\Http\FormRequest;

class CustomerImportSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:100', 'regex:/^import_[A-Za-z0-9_-]+$/'],
        ];
    }
}
