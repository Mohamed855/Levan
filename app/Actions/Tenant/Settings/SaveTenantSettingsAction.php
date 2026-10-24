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
        $newImagePath = $imageFile?->store('settings/tenant', 'public');

        try {
            return DB::transaction(function () use ($data, $newImagePath): Setting {
            $setting = Setting::query()->lockForUpdate()->first() ?? new Setting();
            $previousImagePath = $setting->image;

            if (isset($data['office_name'])) {
                $setting->office_name = $data['office_name'];
            }

            if (isset($data['logo_text'])) {
                $setting->logo_text = $data['logo_text'];
            }

            if ($newImagePath !== null) {
                $setting->image = $newImagePath;
            }

            $currentData = $setting->data ?? [];

            // SMS & communication settings
            if (array_key_exists('sms_provider', $data)) {
                $currentData['sms_provider'] = $data['sms_provider'];
            }
            if (array_key_exists('sms_sender_id', $data)) {
                $currentData['sms_sender_id'] = $data['sms_sender_id'];
            }
            if (filled($data['sms_api_key'] ?? null)) {
                $currentData['sms_api_key'] = encrypt($data['sms_api_key']);
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

            if ($newImagePath !== null && $previousImagePath !== null && $previousImagePath !== $newImagePath) {
                DB::afterCommit(function () use ($previousImagePath): void {
                    if (Storage::disk('public')->exists($previousImagePath)) {
                        Storage::disk('public')->delete($previousImagePath);
                    }
                });
            }

            return $setting;
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }
    }
}
