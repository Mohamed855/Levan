<?php

namespace App\Http\Requests\Tenants\Settings;

use App\Http\Requests\Concerns\WizardFormRequest;

class MainSettingsRequest extends WizardFormRequest
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
    protected function prepareForValidation(): void
    {
        $normalizeDigits = static fn (?string $value): string => str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            (string) $value
        );

        $merge = [];
        foreach (['unified_number', 'fal_license'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = preg_replace('/\D+/', '', $normalizeDigits((string) $this->input($field)));
            }
        }
        if ($this->has('representative_name') && is_string($this->input('representative_name'))) {
            $trimmed = trim($this->input('representative_name'));
            $merge['representative_name'] = $trimmed === '' ? null : $trimmed;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            // عامة
            'office_name' => 'nullable|string|max:255',
            'unified_number' => ['nullable', 'string', 'digits:10', 'regex:/^700\d{7}$/'],
            'fal_license' => ['nullable', 'string', 'max:50', 'regex:/^\d+$/'],
            'representative_name' => ['nullable', 'string', 'min:3', 'max:255'],
            'logo_text' => 'nullable|string|max:14',
            'report_phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'report_unified_phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'report_social' => ['nullable', 'string', 'max:255'],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'establishment_signature' => 'nullable|image|mimes:png|mimetypes:image/png|extensions:png|max:10240',
            'establishment_stamp' => 'nullable|image|mimes:png|mimetypes:image/png|extensions:png|max:10240',
            'remove_image' => ['nullable', 'boolean'],
            'remove_establishment_signature' => ['nullable', 'boolean'],
            'remove_establishment_stamp' => ['nullable', 'boolean'],

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
            'unified_number.digits' => 'الرقم الموحد للمنشأة يجب أن يتكون من 10 أرقام.',
            'unified_number.regex' => 'الرقم الموحد للمنشأة يجب أن يبدأ بـ 700 ويتكون من 10 أرقام.',
            'fal_license.regex' => 'رخصة فال للمزادات العقارية يجب أن تحتوي على أرقام فقط.',
            'representative_name.min' => 'اسم الممثل النظامي للمنشأة يجب ألا يقل عن 3 أحرف.',
            'representative_name.max' => 'اسم الممثل النظامي للمنشأة يجب ألا يتجاوز 255 حرفاً.',
            'logo_text.max' => 'نص الشعار لا يجب أن يتجاوز 14 حرفًا.',
            'image.image' => 'الحقل يجب أن يكون صورة.',
            'image.mimes' => 'الصورة يجب أن تكون من نوع jpeg، png، jpg، gif، webp.',
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
            'unified_number' => 'الرقم الموحد للمنشأة (700)',
            'fal_license' => 'رخصة فال للمزادات العقارية',
            'representative_name' => 'اسم الممثل النظامي للمنشأة',
            'logo_text' => 'النص بجانب الشعار',
            'image' => 'شعار الشركة',
            'establishment_signature' => 'توقيع المنشأة',
            'establishment_stamp' => 'ختم المنشأة',
            'operational_regions' => 'مناطق العمل',
            'operational_regions.*' => 'منطقة العمل',
        ];
    }
}
