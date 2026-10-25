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
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:800'],
            'maintenance_mode' => ['nullable'],
            'trial_days_mode' => ['nullable'],
            'force_two_factor_auth' => ['nullable'],

            //
            'office_address' => ['nullable', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'between:7,15', 'regex:/^[0-9]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'], 'x_url' => ['nullable', 'url', 'max:255'], 'linkedin_url' => ['nullable', 'url', 'max:255'], 'google_maps_embed_url' => ['nullable', 'url', 'max:2000'],

            'etmam_base_url' => ['nullable', 'string', 'max:255'],
            'etmam_system_code' => ['nullable', 'string', 'max:255'],
            'etmam_api_token' => ['nullable', 'string', 'max:255'],
            'etmam_hmac_secret' => ['nullable', 'string', 'max:255'],
            'etmam_enabled' => ['nullable'],

            'microsoft_client_id' => ['sometimes', 'required', 'string'],
            'microsoft_client_secret' => ['sometimes', 'nullable', 'string'],
            'microsoft_redirect_uri' => ['sometimes', 'required', 'url'],
            'microsoft_tenant_id' => ['sometimes', 'required', 'string'],
            'main_email' => ['sometimes', 'required', 'email'],

        ];
    }

    public function messages(): array
    {
        return [
            'office_name.max' => 'اسم المكتب يجب ألا يزيد عن 255 حرفًا.',
            'logo_text.max' => 'نص الشعار يجب ألا يزيد عن 14 حرفًا.',
            'image.image' => 'يجب أن يكون الملف صورة.',
            'image.mimes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 800 كيلوبايت.',

            'office_address.max' => 'عنوان المكتب يجب ألا يزيد عن 255 حرفًا.',
            'commercial_register.max' => 'السجل التجاري يجب ألا يزيد عن 100 حرف.',
            'tax_number.max' => 'الرقم الضريبي يجب ألا يزيد عن 100 حرف.',
            'phone_number.between' => 'رقم الهاتف يجب أن يتكون من 7 إلى 15 رقماً.',
            'phone_number.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط.',
            'email.email' => 'يجب إدخال بريد إلكتروني صالح.',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرفًا.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $fragment = $this->input('active_tab', null);
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withFragment($fragment)
                ->withErrors($validator)
        );
    }
}
