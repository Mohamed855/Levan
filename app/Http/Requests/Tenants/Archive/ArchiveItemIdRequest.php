<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Archive;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveItemIdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $parameters = $this->route()?->parameters() ?? [];
        $this->merge(['id' => reset($parameters)]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return ['id' => ['required', 'integer', 'min:1']];
    }
}
