<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Notification;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationReadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return ['id' => ['required', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['id.required' => 'معرف الإشعار مطلوب.'];
    }

    public function attributes(): array
    {
        return ['id' => 'معرف الإشعار'];
    }
}
