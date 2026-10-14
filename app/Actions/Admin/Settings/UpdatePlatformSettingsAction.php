<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Models\Admin\Settings\Settings;
use App\Services\Admin\Microsoft\ConnectionTester\MicrosoftConnectionTester;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdatePlatformSettingsAction
{
    public function __construct(private readonly MicrosoftConnectionTester $microsoftTester) {}

    /** @param array<string, mixed> $validated @param array<string, bool> $flags */
    public function execute(array $validated, array $flags, ?UploadedFile $image = null): Settings
    {
        $current = Settings::query()->first();
        $microsoftSecret = $this->validatedMicrosoftSecret($current, $validated);
        $storedImage = null;
        $replacedImage = null;

        try {
            $settings = DB::transaction(function () use ($validated, $flags, $image, $microsoftSecret, &$storedImage, &$replacedImage): Settings {
                $settings = Settings::query()->lockForUpdate()->first() ?? new Settings;

                if (array_key_exists('office_name', $validated)) {
                    foreach (['office_name', 'logo_text', 'office_address', 'commercial_register', 'tax_number', 'phone_number', 'email'] as $field) {
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

                if (array_key_exists('etmam_base_url', $validated)) {
                    foreach (['etmam_base_url', 'etmam_system_code', 'etmam_api_token', 'etmam_hmac_secret'] as $field) {
                        $settings->{$field} = $validated[$field];
                    }
                    $settings->etmam_enabled = $flags['etmam_enabled'] ?? false;
                }

                if ($flags['two_factor_settings_submit'] ?? false) {
                    $settings->force_two_factor_auth = $flags['force_two_factor_auth'] ?? false;
                }

                foreach (['default_tenant_version_id', 'default_landing_version_id'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $settings->{$field} = $validated[$field];
                    }
                }

                if ($microsoftSecret !== null) {
                    foreach (['microsoft_client_id', 'microsoft_redirect_uri', 'microsoft_tenant_id', 'main_email'] as $field) {
                        $settings->{$field} = $validated[$field];
                    }
                    if (! empty($validated['microsoft_client_secret'] ?? null)) {
                        $settings->microsoft_client_secret = Crypt::encryptString($microsoftSecret);
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

    /** @param array<string, mixed> $validated */
    private function validatedMicrosoftSecret(?Settings $settings, array $validated): ?string
    {
        if (! array_key_exists('microsoft_client_id', $validated)) {
            return null;
        }

        $secret = $validated['microsoft_client_secret'] ?? null;
        if (empty($secret) && $settings?->microsoft_client_secret) {
            $secret = Crypt::decryptString($settings->microsoft_client_secret);
        }

        if (empty($secret)) {
            throw ValidationException::withMessages([
                'microsoft_client_secret' => 'يجب إدخال Client Secret صالح لاختبار الاتصال مع Microsoft.',
            ]);
        }

        [$valid, $message] = $this->microsoftTester->validate(
            $validated['microsoft_client_id'],
            $secret,
            $validated['microsoft_tenant_id'],
            $validated['main_email'],
        );

        if (! $valid) {
            throw ValidationException::withMessages(['microsoft_client_id' => $message]);
        }

        return $secret;
    }
}
