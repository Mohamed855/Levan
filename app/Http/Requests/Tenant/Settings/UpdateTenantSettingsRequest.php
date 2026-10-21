<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'active_tab' => ['nullable', 'string'],
            'office_name' => ['nullable', 'string', 'max:255'],
            'logo_text' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sms_provider' => ['nullable', 'string', 'max:100'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_enabled' => ['nullable', 'boolean'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'breadcrumb_visibility' => ['nullable', 'boolean'],
            'force_two_factor_auth' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'office_name' => 'اسم المكتب / المنشأة',
            'logo_text' => 'النص التعريفي للشعار',
            'image' => 'شعار المنشأة',
            'sms_sender_id' => 'اسم المرسل (SMS)',
        ];
    }
}
