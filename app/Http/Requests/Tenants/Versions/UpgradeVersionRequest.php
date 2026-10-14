<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Versions;

use Illuminate\Foundation\Http\FormRequest;

class UpgradeVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('tenant')?->can('subscriptions.upgrade');
    }

    public function rules(): array
    {
        return ['target_id' => ['required', 'integer', 'min:1']];
    }

    public function messages(): array
    {
        return [
            'target_id.required' => 'الإصدار المستهدف مطلوب.',
            'target_id.integer' => 'الإصدار المستهدف غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return ['target_id' => 'الإصدار المستهدف'];
    }
}
