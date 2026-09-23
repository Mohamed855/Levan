<?php

namespace App\Helpers\Tenants\Settings;


use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /*
    |--------------------------------------------------------------------------
    | Get Settings
    |--------------------------------------------------------------------------
    */
    public static function getSettings()
    {
        return Cache::remember('app_settings_' . tenant('id'), 60, function () {
            return Settings::first();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على قيمة إعداد معين.
    |--------------------------------------------------------------------------
    */
    public static function get($key, $default = null)
    {
        $settings = self::getSettings();

        if ($settings && isset($settings->$key)) {
            return $settings->$key;
        }

        return $default;
    }
}
