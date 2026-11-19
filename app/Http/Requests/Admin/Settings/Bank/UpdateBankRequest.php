<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Bank;

use Illuminate\Validation\Rule;

class UpdateBankRequest extends StoreBankRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'icon' => [
                Rule::requiredIf(fn (): bool => $this->boolean('remove_icon')),
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,svg',
                'max:10240',
            ],
            'remove_icon' => ['nullable', 'boolean'],
        ];
    }
}
