<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Settings;

use App\Models\Tenants\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateTenantSettingsAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated, ?UploadedFile $image = null): Settings
    {
        $storedFile = null;
        $replacedFile = null;

        try {
            $settings = DB::transaction(function () use ($validated, $image, &$storedFile, &$replacedFile): Settings {
                $settings = Settings::query()->lockForUpdate()->first() ?? new Settings;

                if (array_key_exists('office_name', $validated)) {
                    $settings->office_name = $validated['office_name'];
                }
                if (array_key_exists('logo_text', $validated)) {
                    $settings->logo_text = $validated['logo_text'];
                }

                foreach (['report_phone', 'report_unified_phone', 'report_social'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $settings->{$field} = $validated[$field];
                    }
                }

                if ($image !== null) {
                    $replacedFile = $settings->image;
                    $storedFile = $image->store('image', 'tenant_public');
                    $settings->image = $storedFile;
                }

                if (array_key_exists('operational_regions', $validated)) {
                    $regions = (array) ($validated['operational_regions'] ?? []);
                    $cleaned = array_values(array_filter(
                        $regions,
                        fn ($region) => is_string($region) && trim($region) !== ''
                    ));
                    $settings->operational_regions = ! empty($cleaned) ? $cleaned : null;
                }

                $settings->save();

                return $settings;
            });
        } catch (Throwable $exception) {
            if ($storedFile !== null) {
                Storage::disk('tenant_public')->delete($storedFile);
            }
            throw $exception;
        }

        if ($replacedFile !== null) {
            Storage::disk('tenant_public')->delete($replacedFile);
        }
        Cache::forget('app_settings');
        if (function_exists('tenant') && tenant('id')) {
            Cache::forget('app_settings_'.tenant('id'));
        }

        return $settings;
    }
}
