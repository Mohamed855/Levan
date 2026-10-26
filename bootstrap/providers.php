<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\DashboardNotificationServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MenuServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\TenancyServiceProvider;
use App\Providers\ViewServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Yajra\DataTables\ButtonsServiceProvider;
use Yajra\DataTables\DataTablesServiceProvider;
use Yajra\DataTables\HtmlServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    DashboardNotificationServiceProvider::class,
    FortifyServiceProvider::class,
    MenuServiceProvider::class,
    RouteServiceProvider::class,
    TelescopeServiceProvider::class,
    TenancyServiceProvider::class,
    ViewServiceProvider::class,
    PermissionServiceProvider::class,
    Stancl\Tenancy\TenancyServiceProvider::class,
    ButtonsServiceProvider::class,
    DataTablesServiceProvider::class,
    HtmlServiceProvider::class,
];
