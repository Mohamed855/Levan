<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Settings;

use App\Models\Tenant\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SaveTenantSettingsAction
{
    /**
     * @param array<string, mixed> $data
     * @param UploadedFile|null $imageFile
     */
    public function execute(array $data, ?UploadedFile $imageFile = null): Setting
    {
        return DB::transaction(function () use ($data, $imageFile): Setting {
            $setting = Setting::query()->first() ?? new Setting();

            if (isset($data['office_name'])) {
                $setting->office_name = $data['office_name'];
            }

            if (isset($data['logo_text'])) {
                $setting->logo_text = $data['logo_text'];
            }

            if ($imageFile) {
                if ($setting->image && Storage::disk('public')->exists($setting->image)) {
                    Storage::disk('public')->delete($setting->image);
                }
                $setting->image = $imageFile->store('settings/tenant', 'public');
            }

            $currentData = $setting->data ?? [];

            // SMS & communication settings
            if (array_key_exists('sms_provider', $data)) {
                $currentData['sms_provider'] = $data['sms_provider'];
            }
            if (array_key_exists('sms_sender_id', $data)) {
                $currentData['sms_sender_id'] = $data['sms_sender_id'];
            }
            if (array_key_exists('sms_api_key', $data)) {
                $currentData['sms_api_key'] = $data['sms_api_key'];
            }
            if (array_key_exists('sms_enabled', $data)) {
                $currentData['sms_enabled'] = (bool) $data['sms_enabled'];
            }

            // System preferences
            if (array_key_exists('maintenance_mode', $data)) {
                $currentData['maintenance_mode'] = (bool) $data['maintenance_mode'];
            }
            if (array_key_exists('breadcrumb_visibility', $data)) {
                $currentData['breadcrumb_visibility'] = (bool) $data['breadcrumb_visibility'];
            }
            if (array_key_exists('force_two_factor_auth', $data)) {
                $currentData['force_two_factor_auth'] = (bool) $data['force_two_factor_auth'];
            }

            $setting->data = $currentData;
            $setting->save();

            return $setting;
        });
    }
}
