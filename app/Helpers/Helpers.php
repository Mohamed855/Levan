<?php

namespace App\Helpers;

use App\Models\Item;
use App\Models\crm\Contract;
use App\Models\crm\Offers;
use App\Models\crm\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Helpers
{
    public static function appClasses()
    {
        $data = config('custom.custom');

        // تعيين اللغة الافتراضية للعربية إذا لم يتم تعيينها
        if (!session()->has('locale')) {
            session()->put('locale', 'ar');
            app()->setLocale('ar');
        } else {
            app()->setLocale(session()->get('locale'));
        }

        // default data array
        $DefaultData = [
            'myLayout' => 'vertical',
            'myTheme' => 'theme-default',
            'myStyle' => 'light',
            'myRTLSupport' => true,
            'myRTLMode' => true,
            'hasCustomizer' => true,
            'showDropdownOnHover' => true,
            'displayCustomizer' => true,
            'contentLayout' => 'compact',
            'headerType' => 'fixed',
            'navbarType' => 'fixed',
            'menuFixed' => true,
            'menuCollapsed' => false,
            'footerFixed' => false,
            'customizerControls' => [
                'rtl',
                'style',
                'headerType',
                'contentLayout',
                'layoutCollapsed',
                'showDropdownOnHover',
                'layoutNavbarOptions',
                'themes',
            ],
        ];

        $data = array_merge($DefaultData, $data);

        $allOptions = [
            'myLayout' => ['vertical', 'horizontal', 'blank', 'front'],
            'menuCollapsed' => [true, false],
            'hasCustomizer' => [true, false],
            'showDropdownOnHover' => [true, false],
            'displayCustomizer' => [true, false],
            'contentLayout' => ['compact', 'wide'],
            'headerType' => ['fixed', 'static'],
            'navbarType' => ['fixed', 'static', 'hidden'],
            'myStyle' => ['light', 'dark', 'system'],
            'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
            'myRTLSupport' => [true, false],
            'myRTLMode' => [true, false],
            'menuFixed' => [true, false],
            'footerFixed' => [true, false],
            'customizerControls' => [],
        ];

        foreach ($allOptions as $key => $value) {
            if (array_key_exists($key, $DefaultData)) {
                if (gettype($DefaultData[$key]) === gettype($data[$key])) {
                    if (is_string($data[$key])) {
                        if (isset($data[$key]) && $data[$key] !== null) {
                            if (!array_key_exists($data[$key], $value)) {
                                $result = array_search($data[$key], $value, 'strict');
                                if (empty($result) && $result !== 0) {
                                    $data[$key] = $DefaultData[$key];
                                }
                            }
                        } else {
                            $data[$key] = $DefaultData[$key];
                        }
                    }
                } else {
                    $data[$key] = $DefaultData[$key];
                }
            }
        }

        $styleVal = $data['myStyle'] == "dark" ? "dark" : "light";
        $styleUpdatedVal = $data['myStyle'] == "dark" ? "dark" : $data['myStyle'];
        $layoutName = $data['myLayout'];
        $isAdmin = Str::contains($layoutName, 'front') ? false : true;

        $modeCookieName = $isAdmin ? 'admin-mode' : 'front-mode';
        $colorPrefCookieName = $isAdmin ? 'admin-colorPref' : 'front-colorPref';

        // Disabled theme switching to dark mode via cookie
        /*
        if ($layoutName !== 'blank') {
            if (isset($_COOKIE[$modeCookieName])) {
                $styleVal = $_COOKIE[$modeCookieName];
                if ($styleVal === 'system') {
                    $styleVal = isset($_COOKIE[$colorPrefCookieName]) ? $_COOKIE[$colorPrefCookieName] : 'light';
                }
                $styleUpdatedVal = $_COOKIE[$modeCookieName];
            }
        }
        */

        isset($_COOKIE['theme']) ? $themeVal = $_COOKIE['theme'] : $themeVal = $data['myTheme'];

        $directionVal = isset($_COOKIE['direction']) ? ($_COOKIE['direction'] === "true" ? 'rtl' : 'rtl') : $data['myRTLMode'];

        if (session()->get('locale') == 'ar') {
            $layoutClasses['rtlMode'] = 'rtl';
            $layoutClasses['textDirection'] = 'rtl';
        } else {
            $layoutClasses['rtlMode'] = 'rtl';
            $layoutClasses['textDirection'] = 'rtl';
        }

        $layoutClasses = [
            'layout' => $data['myLayout'],
            'theme' => $themeVal,
            'themeOpt' => $data['myTheme'],
            'style' => $styleVal,
            'styleOpt' => $data['myStyle'],
            'styleOptVal' => $styleUpdatedVal,
            'rtlSupport' => $data['myRTLSupport'],
            'rtlMode' => $data['myRTLMode'],
            'textDirection' => $directionVal,
            'menuCollapsed' => $data['menuCollapsed'],
            'hasCustomizer' => $data['hasCustomizer'],
            'showDropdownOnHover' => $data['showDropdownOnHover'],
            'displayCustomizer' => $data['displayCustomizer'],
            'contentLayout' => $data['contentLayout'],
            'headerType' => $data['headerType'],
            'navbarType' => $data['navbarType'],
            'menuFixed' => $data['menuFixed'],
            'footerFixed' => $data['footerFixed'],
            'customizerControls' => $data['customizerControls'],
        ];

        if ($layoutClasses['menuCollapsed'] == true) {
            $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
        }

        if ($layoutClasses['headerType'] == 'fixed') {
            $layoutClasses['headerType'] = 'layout-menu-fixed';
        }

        if ($layoutClasses['navbarType'] == 'fixed') {
            $layoutClasses['navbarType'] = 'layout-navbar-fixed';
        } elseif ($layoutClasses['navbarType'] == 'static') {
            $layoutClasses['navbarType'] = '';
        } else {
            $layoutClasses['navbarType'] = 'layout-navbar-hidden';
        }

        if ($layoutClasses['menuFixed'] == true) {
            $layoutClasses['menuFixed'] = 'layout-menu-fixed';
        }

        if ($layoutClasses['footerFixed'] == true) {
            $layoutClasses['footerFixed'] = 'layout-footer-fixed';
        }

        if ($layoutClasses['rtlSupport'] == true) {
            $layoutClasses['rtlSupport'] = '/rtl';
        }

        if ($layoutClasses['rtlMode'] == true) {
            $layoutClasses['rtlMode'] = 'rtl';
            $layoutClasses['textDirection'] = 'rtl';
        } else {
            $layoutClasses['rtlMode'] = 'rtl';
            $layoutClasses['textDirection'] = 'rtl';
        }

        if ($layoutClasses['showDropdownOnHover'] == true) {
            $layoutClasses['showDropdownOnHover'] = true;
        } else {
            $layoutClasses['showDropdownOnHover'] = false;
        }

        if ($layoutClasses['displayCustomizer'] == true) {
            $layoutClasses['displayCustomizer'] = true;
        } else {
            $layoutClasses['displayCustomizer'] = false;
        }

        return $layoutClasses;
    }

    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        if (isset($pageConfigs)) {
            if (count($pageConfigs) > 0) {
                foreach ($pageConfigs as $config => $val) {
                    Config::set('custom.' . $demo . '.' . $config, $val);
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////
    ///////// New Helper Functions To Active Route /////////////
    ////////////////////////////////////////////////////////////
    /**
     * Check if current route is active
     * Works with multitenancy path-based routing
     * 
     * @param string|array $route Route name pattern
     * @return bool
     */
    public static function isActiveRoute($route)
    {
        $currentRoute = Route::currentRouteName();

        if (empty($currentRoute)) {
            return false;
        }

        // Handle array of routes
        if (is_array($route)) {
            foreach ($route as $r) {
                if (self::isActiveRoute($r)) {
                    return true;
                }
            }
            return false;
        }

        // Remove 'path.' prefix from current route
        $routeWithoutPrefix = Str::after($currentRoute, 'path.');

        // Direct match without prefix
        if ($routeWithoutPrefix === $route) {
            return true;
        }

        // Direct match with prefix
        if ($currentRoute === "path.{$route}") {
            return true;
        }

        // Wildcard match without prefix (e.g., bills.*)
        if (Str::contains($route, '*')) {
            if (Str::is($route, $routeWithoutPrefix)) {
                return true;
            }

            if (Str::is("path.{$route}", $currentRoute)) {
                return true;
            }
        }

        return false;
    }
    ////////////////////////////////////////////////////////////
    ///////// New Helper Functions To Active Route /////////////
    ////////////////////////////////////////////////////////////
}
