<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clients;

use Illuminate\Foundation\Http\FormRequest;

class CheckCommercialNumberUniqueRequest extends FormRequest
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
            'commercial_number' => ['required', 'string', 'digits:10'],
            'company_id' => ['nullable', 'string'],
        ];
    }
}
