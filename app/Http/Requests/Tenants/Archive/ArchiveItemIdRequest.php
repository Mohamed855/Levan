<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Archive;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveItemIdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $parameters = array_values($this->route()?->parameters() ?? []);
        $id = $parameters[0] ?? null;

        $this->merge([
            'id' => is_object($id) && method_exists($id, 'getKey') ? $id->getKey() : $id,
        ]);
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
