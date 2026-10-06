<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Contract;

use Illuminate\Foundation\Http\FormRequest;

class CheckContractNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user?->can('contracts.create') || $user?->can('contracts.edit');
    }

    public function rules(): array
    {
        return [
            'contract_number' => ['required', 'string', 'max:100'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
        ];
    }
}
