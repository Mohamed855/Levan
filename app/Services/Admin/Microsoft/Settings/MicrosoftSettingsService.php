<?php

namespace App\Services\Admin\Microsoft\Settings;


use App\Models\Admin\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MicrosoftSettingsService
{
    public function get(): array
    {
        $setting = Cache::remember('settings.microsoft', 60, function () {
            return Settings::first();
        });

        $clientSecret = '';

        if ($setting && $setting->microsoft_client_secret) {
            try {
                $clientSecret = Crypt::decryptString($setting->microsoft_client_secret);
            } catch (\Throwable $e) {
                $clientSecret = $setting->microsoft_client_secret;
            }
        }

        $config = [
            'client_id'     => $setting?->microsoft_client_id ?? '',
            'client_secret' => $clientSecret ?? '',
            'redirect_uri'  => $setting?->microsoft_redirect_uri ?? '',
            'tenant_id'     => $setting?->microsoft_tenant_id ?? 'common',
            'main_email'    => $setting?->main_email ?? '',
        ];

        config([
            'services.microsoft.client_id'     => $config['client_id'],
            'services.microsoft.client_secret' => $config['client_secret'],
            'services.microsoft.redirect'      => $config['redirect_uri'],
            'services.microsoft.tenant_id'     => $config['tenant_id'],
        ]);

        return $config;
    }
}
