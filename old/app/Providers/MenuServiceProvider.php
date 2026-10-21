<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $verticalMenuJson = file_get_contents(base_path('resources/assets/menu/tenant/verticalMenu.json'));
        $verticalMenuData = json_decode($verticalMenuJson);

        $this->app->make('view')->share('menuData', [$verticalMenuData]);
    }
}
