<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\DeleteBulk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TenantMassDeleteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('ids'))) {
            $this->merge(['ids' => array_filter(explode(',', $this->input('ids')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string', Rule::in(['Assignment', 'Auction', 'Property'])],
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
            'password' => ['required', 'string'],
        ];
    }
}
