<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use Illuminate\Foundation\Http\FormRequest;

class ValidateWizardStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check() || auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'target_path' => ['required', 'string', 'max:2048'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'max:255'],
        ];
    }
}
