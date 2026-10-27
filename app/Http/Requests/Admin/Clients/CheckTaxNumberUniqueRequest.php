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

    protected function prepareForValidation(): void
    {
        if ($this->has('tax_number')) {
            $val = (string) $this->input('tax_number');
            $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $this->merge(['tax_number' => trim(str_replace($arabic, $western, $val))]);
        }
    }

    public function rules(): array
    {
        return [
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{13}3$/'],
            'company_id' => ['nullable', 'string'],
        ];
    }
}
