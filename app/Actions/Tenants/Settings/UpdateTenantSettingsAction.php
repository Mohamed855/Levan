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
    public function execute(array $validated, ?UploadedFile $image = null, ?UploadedFile $signature = null, ?UploadedFile $stamp = null): Settings
    {
        $storedFiles = [];
        $replacedFiles = [];

        try {
            $settings = DB::transaction(function () use ($validated, $image, $signature, $stamp, &$storedFiles, &$replacedFiles): Settings {
                $settings = Settings::query()->lockForUpdate()->first() ?? new Settings;

                if (array_key_exists('office_name', $validated)) {
                    $settings->office_name = $validated['office_name'];
                }
                if (array_key_exists('logo_text', $validated)) {
                    $settings->logo_text = $validated['logo_text'];
                }

                foreach (['unified_number', 'fal_license', 'representative_name', 'report_phone', 'report_unified_phone', 'report_social'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $settings->{$field} = $validated[$field];
                    }
                }

                foreach (['image' => $image, 'establishment_signature' => $signature, 'establishment_stamp' => $stamp] as $field => $upload) {
                    $disk = $field === 'image' ? 'tenant_public' : 'tenant_private';
                    if ($upload !== null) {
                        $directory = $field === 'image' ? 'image' : 'settings/signing-assets';
                        $path = $upload->store($directory, $disk);
                        if ($path === false) {
                            throw new \RuntimeException('Unable to store the settings image.');
                        }
                        $storedFiles[] = [$disk, $path];
                        if ($settings->{$field}) {
                            $replacedFiles[] = [$disk, $settings->{$field}];
                        }
                        $settings->{$field} = $path;
                    } elseif (! empty($validated['remove_'.$field])) {
                        if ($settings->{$field}) {
                            $replacedFiles[] = [$disk, $settings->{$field}];
                        }
                        $settings->{$field} = null;
                    }
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
            foreach ($storedFiles as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $exception;
        }

        foreach ($replacedFiles as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }
        Cache::forget('app_settings');
        if (function_exists('tenant') && tenant('id')) {
            Cache::forget('app_settings_'.tenant('id'));
        }

        return $settings;
    }
}
