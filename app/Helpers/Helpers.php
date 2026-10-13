<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Helpers
{
    public static function appClasses(): array
    {
        session()->put('locale', 'ar');
        app()->setLocale('ar');

        $data = array_merge([
            'myLayout' => 'vertical',
            'contentLayout' => 'compact',
            'headerType' => 'fixed',
            'navbarType' => 'fixed',
            'menuFixed' => true,
            'menuCollapsed' => false,
            'footerFixed' => false,
            'showDropdownOnHover' => true,
        ], config('custom.custom', []));

        $layout = in_array($data['myLayout'], ['vertical', 'horizontal', 'blank', 'front'], true)
            ? $data['myLayout']
            : 'vertical';
        $contentLayout = in_array($data['contentLayout'], ['compact', 'wide'], true)
            ? $data['contentLayout']
            : 'compact';
        $navbarType = in_array($data['navbarType'], ['fixed', 'static', 'hidden'], true)
            ? $data['navbarType']
            : 'fixed';

        return [
            'layout' => $layout,
            'theme' => 'theme-semi-dark',
            'themeOpt' => 'theme-semi-dark',
            'style' => 'light',
            'styleOpt' => 'light',
            'styleOptVal' => 'light',
            'rtlSupport' => '/rtl',
            'rtlMode' => 'rtl',
            'textDirection' => 'rtl',
            'menuCollapsed' => $data['menuCollapsed'] ? 'layout-menu-collapsed' : '',
            'hasCustomizer' => false,
            'showDropdownOnHover' => (bool) $data['showDropdownOnHover'],
            'displayCustomizer' => false,
            'contentLayout' => $contentLayout,
            'headerType' => $data['headerType'] === 'fixed' ? 'layout-menu-fixed' : '',
            'navbarType' => match ($navbarType) {
                'fixed' => 'layout-navbar-fixed',
                'hidden' => 'layout-navbar-hidden',
                default => '',
            },
            'menuFixed' => $data['menuFixed'] ? 'layout-menu-fixed' : '',
            'footerFixed' => $data['footerFixed'] ? 'layout-footer-fixed' : '',
            'customizerControls' => [],
        ];
    }

    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        if (isset($pageConfigs)) {
            if (count($pageConfigs) > 0) {
                foreach ($pageConfigs as $config => $val) {
                    Config::set('custom.'.$demo.'.'.$config, $val);
                }
            }
        }
    }

    // //////////////////////////////////////////////////////////
    // /////// New Helper Functions To Active Route /////////////
    // //////////////////////////////////////////////////////////
    /**
     * Check if current route is active
     * Works with multitenancy path-based routing
     *
     * @param  string|array  $route  Route name pattern
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
    // //////////////////////////////////////////////////////////
    // /////// New Helper Functions To Active Route /////////////
    // //////////////////////////////////////////////////////////
}
