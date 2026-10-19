<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use Illuminate\Foundation\Http\FormRequest;

class ValidateWizardStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The target FormRequest is authorized again by WizardDraftController.
        // Keeping this transport request public also allows public website forms
        // to use the same server-side validation contract.
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('target_method') || empty($this->input('target_method'))) {
            $method = $this->input('_method') ?: ($this->isMethod('POST') ? 'POST' : $this->method());
            $this->merge([
                'target_method' => strtoupper((string) $method),
            ]);
        } else {
            $this->merge([
                'target_method' => strtoupper((string) $this->input('target_method')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'target_path' => ['required', 'string', 'max:2048'],
            'target_method' => ['nullable', 'string', 'in:POST,PUT,PATCH'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'max:255'],
        ];
    }
}
