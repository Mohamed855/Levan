<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use Illuminate\Foundation\Http\FormRequest;

class StoreWizardDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check() || auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'resource_key' => ['required', 'string', 'max:255'],
            'step' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
