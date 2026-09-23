<?php

namespace App\Providers;

use App\View\Composers\TenantVersionBadgeComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // التحديثات الجديدة للعملاء
        View::composer(['tenants.layouts.sections.navbar.navbar',], TenantVersionBadgeComposer::class);
    }

    public function register()
    {
        //
    }
}
