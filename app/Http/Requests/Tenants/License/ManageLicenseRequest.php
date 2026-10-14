<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\License;

use Illuminate\Foundation\Http\FormRequest;

class ManageLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('subscriptions.add_licenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'المستخدم مطلوب.',
            'user_id.exists' => 'المستخدم المحدد غير موجود.',
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'المستخدم'];
    }
}
