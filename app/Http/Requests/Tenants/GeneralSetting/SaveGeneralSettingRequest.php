<?php

namespace App\Http\Requests\Tenants\GeneralSetting;

use Illuminate\Foundation\Http\FormRequest;

class SaveGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.string' => 'يجب أن يكون الاسم نصاً.',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرفاً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
        ];
    }
}
