<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Models\Admin\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdatePlatformSettingsAction
{
    /** @param array<string, mixed> $validated @param array<string, bool> $flags */
    public function execute(array $validated, array $flags, ?UploadedFile $image = null): Settings
    {
        $storedImage = null;
        $replacedImage = null;

        try {
            $settings = DB::transaction(function () use ($validated, $flags, $image, &$storedImage, &$replacedImage): Settings {
                $settings = Settings::query()->lockForUpdate()->first() ?? new Settings;

                if (array_key_exists('office_name', $validated)) {
                    foreach (['office_name', 'logo_text', 'office_address', 'commercial_register', 'tax_number', 'phone_number', 'email', 'facebook_url', 'x_url', 'linkedin_url', 'google_maps_embed_url'] as $field) {
                        $settings->{$field} = $validated[$field] ?? null;
                    }
                    $settings->maintenance_mode = $flags['maintenance_mode'] ?? false;
                    $settings->trial_days_mode = $flags['trial_days_mode'] ?? false;

                    if ($image !== null) {
                        $replacedImage = $settings->image;
                        $storedImage = $image->store('image', 'public');
                        $settings->image = $storedImage;
                    }
                }

                $settings->save();

                return $settings;
            });
        } catch (Throwable $exception) {
            if ($storedImage !== null) {
                Storage::disk('public')->delete($storedImage);
            }
            throw $exception;
        }

        if ($storedImage !== null && $replacedImage !== null) {
            Storage::disk('public')->delete($replacedImage);
        }
        Cache::forget('app_settings');

        return $settings;
    }
}
