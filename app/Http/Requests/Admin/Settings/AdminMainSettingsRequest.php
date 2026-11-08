<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminMainSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('system.settings') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('google_maps_embed_url') && is_string($this->input('google_maps_embed_url'))) {
            $value = trim($this->input('google_maps_embed_url'));
            if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches)) {
                $this->merge(['google_maps_embed_url' => $matches[1]]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'office_name' => ['nullable', 'string', 'max:255'],
            'logo_text' => ['nullable', 'string', 'max:14'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            'maintenance_mode' => ['nullable'],
            'trial_days_mode' => ['nullable'],

            //
            'office_address' => ['nullable', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'between:7,15', 'regex:/^[0-9]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'], 'x_url' => ['nullable', 'url', 'max:255'], 'linkedin_url' => ['nullable', 'url', 'max:255'], 'google_maps_embed_url' => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'office_name.max' => 'اسم المكتب يجب ألا يزيد عن 255 حرفًا.',
            'logo_text.max' => 'نص الشعار يجب ألا يزيد عن 14 حرفًا.',
            'image.image' => 'يجب أن يكون الملف صورة.',
            'image.mimes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 10 ميجابايت.',

            'office_address.max' => 'عنوان المكتب يجب ألا يزيد عن 255 حرفًا.',
            'commercial_register.max' => 'السجل التجاري يجب ألا يزيد عن 100 حرف.',
            'tax_number.max' => 'الرقم الضريبي يجب ألا يزيد عن 100 حرف.',
            'phone_number.between' => 'رقم الهاتف يجب أن يتكون من 7 إلى 15 رقماً.',
            'phone_number.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط.',
            'email.email' => 'يجب إدخال بريد إلكتروني صالح.',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرفًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            'office_name' => 'إسم الشركة', 'logo_text' => 'النص بجانب الشعار', 'image' => 'شعار الموقع / الشعار العام',
            'maintenance_mode' => 'وضع الصيانة', 'trial_days_mode' => 'فترة التجربة',
            'office_address' => 'عنوان المكتب', 'commercial_register' => 'السجل التجاري', 'tax_number' => 'الرقم الضريبي',
            'phone_number' => 'رقم الهاتف', 'email' => 'البريد الإلكتروني', 'facebook_url' => 'رابط Facebook', 'x_url' => 'رابط X',
            'linkedin_url' => 'رابط LinkedIn', 'google_maps_embed_url' => 'رابط Google Maps Embed',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
        );
    }
}
