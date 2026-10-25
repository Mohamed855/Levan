<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clients;

use Illuminate\Foundation\Http\FormRequest;

class CheckTaxNumberUniqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user('admin')?->can('clients.create')
            || $this->user('admin')?->can('clients.edit')
            || $this->user('admin')?->can('companies.create')
            || $this->user('admin')?->can('companies.edit'));
    }

    public function rules(): array
    {
        return [
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{14}$/'],
            'company_id' => ['nullable', 'string'],
        ];
    }
}
