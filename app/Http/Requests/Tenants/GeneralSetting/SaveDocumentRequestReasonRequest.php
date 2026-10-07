<?php

namespace App\Http\Requests\Tenants\GeneralSetting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDocumentRequestReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user?->can('document_request_reasons.view') || $user?->can('system.settings');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings_document_request_reasons', 'name')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم سبب طلب المستندات مطلوب.',
            'name.string' => 'اسم سبب طلب المستندات يجب أن يكون نصاً.',
            'name.max' => 'اسم سبب طلب المستندات يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'اسم سبب طلب المستندات موجود مسبقاً.',
        ];
    }
}
