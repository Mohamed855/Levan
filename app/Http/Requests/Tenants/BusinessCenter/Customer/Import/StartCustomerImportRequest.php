<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Import;

use Illuminate\Foundation\Http\FormRequest;

class StartCustomerImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'temp_path' => ['required', 'string', 'max:255', 'regex:/^temp\\/imports\\/[A-Za-z0-9_.-]+$/'],
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
        ];
    }
}
