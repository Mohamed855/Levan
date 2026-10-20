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
     * @param  array<string, bool>  $flags
     * @param  array<string, UploadedFile>  $files
     */
    public function execute(string $activeTab, array $validated, array $flags, array $files): Settings
    {
        $storedFiles = [];
        $replacedFiles = [];

        try {
            $settings = DB::transaction(function () use ($activeTab, $validated, $flags, $files, &$storedFiles, &$replacedFiles): Settings {
                $settings = Settings::query()->lockForUpdate()->first() ?? new Settings;

                if ($activeTab === '#generalSettings') {
                    $settings->office_name = $validated['office_name'];
                    $settings->logo_text = $validated['logo_text'];
                    $settings->maintenance_mode = $flags['maintenance_mode'] ?? false;
                    $settings->breadcrumb_visibility = $flags['breadcrumb_visibility'] ?? false;
                    $this->replaceFile($settings, 'image', $files, 'image', $storedFiles, $replacedFiles);
                }

                if ($activeTab === '#printSettings') {
                    foreach (['horizontal_header_image', 'vertical_header_image', 'horizontal_footer_image', 'vertical_footer_image', 'signature'] as $field) {
                        $this->replaceFile($settings, $field, $files, 'settings/print', $storedFiles, $replacedFiles);
                    }
                }

                if ($activeTab === '#SmsSettings') {
                    foreach (['sms_provider', 'sms_api_key', 'sms_api_secret', 'sms_api_endpoint', 'sms_sender_id', 'sms_from_number', 'sms_callback_url', 'sms_rate_limit'] as $field) {
                        if (array_key_exists($field, $validated)) {
                            $settings->{$field} = $validated[$field];
                        }
                    }

                    $smsEnabled = $flags['sms_enabled'] ?? false;
                    $settings->sms_enabled = $smsEnabled;
                    $settings->sms_auto_reminders_enabled = $smsEnabled && ($flags['sms_auto_reminders_enabled'] ?? false);
                }

                if ($activeTab === '#twoFactorSettings') {
                    $settings->force_two_factor_auth = $flags['force_two_factor_auth'] ?? false;
                }

                if (array_key_exists('support_emails', $validated)) {
                    $settings->support_emails = $validated['support_emails'];
                }

                $settings->save();

                return $settings;
            });
        } catch (Throwable $exception) {
            Storage::disk('tenant_public')->delete($storedFiles);
            throw $exception;
        }

        Storage::disk('tenant_public')->delete(array_filter($replacedFiles));
        Cache::forget('app_settings');

        return $settings;
    }

    /**
     * @param  array<string, UploadedFile>  $files
     * @param  list<string>  $storedFiles
     * @param  list<string|null>  $replacedFiles
     */
    private function replaceFile(Settings $settings, string $field, array $files, string $directory, array &$storedFiles, array &$replacedFiles): void
    {
        if (! isset($files[$field])) {
            return;
        }

        $replacedFiles[] = $settings->{$field};
        $path = $files[$field]->store($directory, 'tenant_public');
        $storedFiles[] = $path;
        $settings->{$field} = $path;
    }
}
