<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Archive;

use Illuminate\Foundation\Http\FormRequest;

class BulkArchiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
