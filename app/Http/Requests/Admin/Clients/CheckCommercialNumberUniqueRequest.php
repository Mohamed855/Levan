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

    protected function prepareForValidation(): void
    {
        if ($this->has('commercial_number')) {
            $val = (string) $this->input('commercial_number');
            $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $this->merge(['commercial_number' => trim(str_replace($arabic, $western, $val))]);
        }
    }

    public function rules(): array
    {
        return [
            'commercial_number' => ['required', 'string', 'digits:10', 'regex:/^\d{10}$/'],
            'company_id' => ['nullable', 'string'],
        ];
    }
}
