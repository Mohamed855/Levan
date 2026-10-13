<?php

namespace App\Providers;

use App\View\Composers\TenantVersionBadgeComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // التحديثات الجديدة للعملاء
        View::composer(['tenants.layouts.sections.navbar.navbar'], TenantVersionBadgeComposer::class);

        // تسجيل مكونات shared لدعم الاستدعاء المختصر مثل <x-form...> وأيضاً <x-shared.form...>
        Blade::anonymousComponentPath(resource_path('views/components/shared'));
        Blade::anonymousComponentPath(resource_path('views/components/shared'), 'shared');
    }

    public function register()
    {
        //
    }
}
