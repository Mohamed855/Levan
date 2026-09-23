<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\MenuServiceProvider::class,
    App\Providers\ViewServiceProvider::class,

    // Tenancy
    Stancl\Tenancy\TenancyServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,

    // Third-party
    Spatie\Permission\PermissionServiceProvider::class,
    Yajra\DataTables\DataTablesServiceProvider::class,
    Yajra\DataTables\HtmlServiceProvider::class,
    Yajra\DataTables\ButtonsServiceProvider::class,
];
