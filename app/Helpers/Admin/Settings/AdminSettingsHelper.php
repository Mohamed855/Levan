<?php

namespace App\Helpers\Admin\Settings;

use App\Models\Admin\Settings\Settings;
use Illuminate\Support\Facades\Cache;

class AdminSettingsHelper
{
    /*
    |--------------------------------------------------------------------------
    | Get Settings
    |--------------------------------------------------------------------------
    */
    public static function getSettings()
    {
        return Cache::remember('app_settings', 60, function () {
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
