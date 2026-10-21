<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            // General
            'office_name'          => ['nullable', 'string', 'max:255'],
            'logo_text'            => ['nullable', 'string', 'max:50'],
            'office_address'       => ['nullable', 'string', 'max:255'],
            'commercial_register'  => ['nullable', 'string', 'max:100'],
            'tax_number'           => ['nullable', 'string', 'max:100'],
            'phone_number'         => ['nullable', 'string', 'max:30'],
            'email'                => ['nullable', 'email', 'max:255'],
            'maintenance_mode'     => ['nullable', 'boolean'],
            'trial_days_mode'      => ['nullable', 'boolean'],
            'trial_days_count'     => ['nullable', 'integer', 'min:1', 'max:90'],

            // Social & Contact
            'facebook_url'         => ['nullable', 'url', 'max:255'],
            'x_url'                => ['nullable', 'url', 'max:255'],
            'linkedin_url'         => ['nullable', 'url', 'max:255'],
            'google_maps_embed_url'=> ['nullable', 'string', 'max:2000'],

            // ETMAM API
            'etmam_base_url'       => ['nullable', 'string', 'max:255'],
            'etmam_system_code'    => ['nullable', 'string', 'max:255'],
            'etmam_api_token'      => ['nullable', 'string', 'max:255'],
            'etmam_hmac_secret'    => ['nullable', 'string', 'max:255'],
            'etmam_enabled'        => ['nullable', 'boolean'],

            // Microsoft Graph / Email
            'microsoft_client_id'     => ['nullable', 'string', 'max:255'],
            'microsoft_client_secret' => ['nullable', 'string', 'max:255'],
            'microsoft_redirect_uri'  => ['nullable', 'url', 'max:255'],
            'microsoft_tenant_id'     => ['nullable', 'string', 'max:255'],
            'main_email'              => ['nullable', 'email', 'max:255'],

            // Security
            'force_two_factor_auth' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'             => 'يجب إدخال بريد إلكتروني صالح.',
            'main_email.email'        => 'يجب إدخال بريد إلكتروني رئيسي صالح.',
            'facebook_url.url'        => 'رابط الفيسبوك غير صالح.',
            'x_url.url'               => 'رابط منصة إكس غير صالح.',
            'linkedin_url.url'        => 'رابط لينكد إن غير صالح.',
            'microsoft_redirect_uri.url' => 'رابط إعادة توجيه مايكروسوفت غير صالح.',
        ];
    }
}
