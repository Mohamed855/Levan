<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Bank;

class UpdateBankRequest extends StoreBankRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:10240'],
        ];
    }
}
