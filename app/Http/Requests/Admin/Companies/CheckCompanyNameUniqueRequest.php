<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

class CheckCompanyNameUniqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('companies.create') || $this->user('admin')?->can('companies.edit');
    }

    public function rules(): array
    {
        return ['company_name' => ['required', 'string', 'max:255'], 'company_id' => ['nullable', 'string']];
    }
}
