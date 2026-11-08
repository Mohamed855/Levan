<?php

namespace App\Http\Requests\Tenants\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MainSettingsRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization for the request.
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('system.settings') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation rules for the main settings.
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            // عامة
            'office_name' => 'nullable|string|max:255',
            'logo_text' => 'nullable|string|max:14',
            'report_phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'report_unified_phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'report_social' => ['nullable', 'string', 'max:255'],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',

            // مناطق العمل
            'operational_regions' => 'nullable|array',
            'operational_regions.*' => 'string|max:100',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom validation messages.
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'office_name.string' => 'اسم المكتب يجب أن يكون نصًا.',
            'office_name.max' => 'اسم المكتب لا يجب أن يتجاوز 255 حرفًا.',
            'logo_text.max' => 'نص الشعار لا يجب أن يتجاوز 14 حرفًا.',
            'image.image' => 'الحقل يجب أن يكون صورة.',
            'image.mimes' => 'الصورة يجب أن تكون من نوع jpeg، png، jpg، gif.',
            'image.max' => 'حجم الصورة لا يجب أن يتجاوز 10 ميجابايت.',

            'operational_regions.array' => 'حقل مناطق العمل يجب أن يكون مصفوفة.',
            'operational_regions.*.string' => 'منطقة العمل يجب أن تكون نصاً.',
            'operational_regions.*.max' => 'منطقة العمل لا يجب أن تتجاوز 100 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'office_name' => 'إسم الشركة',
            'logo_text' => 'النص بجانب الشعار',
            'image' => 'شعار الشركة',
            'operational_regions' => 'مناطق العمل',
            'operational_regions.*' => 'منطقة العمل',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Handle a failed validation attempt.
    |--------------------------------------------------------------------------
    */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
        );
    }
}
