<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Events\RevertedToCentralContext;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    // Jobs\CreateDatabase::class,
                    // Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    // Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                \Stancl\Tenancy\Listeners\BootstrapTenancy::class,
            ],
            Events\TenancyEnded::class => [
                \Stancl\Tenancy\Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }


    // الكود الجديد -- يحتاج اختبار بعد تفعيله ليتاكد ان الجلسات لا تتداخل ببعض
    public function boot()
    {
        $this->bootEvents();
        $this->makeTenancyMiddlewareHighestPriority();

        //  عند تهيئة الـ tenant
        Event::listen(TenancyInitialized::class, function (TenancyInitialized $event) {
            $tenantId = $event->tenancy->tenant->getTenantKey();

            // ← تأكد أن الطلب الحالي فعلاً داخل سياق التيننت
            if (!app()->runningInConsole()) {
                Config::set('session.cookie',  'tenant_' . $tenantId . '_session');
                Config::set('session.path',    '/' . $tenantId);
            }

            Config::set(
                'filesystems.disks.tenant_public.root',
                storage_path("app/tenants/{$tenantId}/public")
            );
            Config::set(
                'filesystems.disks.tenant_public.url',
                url("tenants/{$tenantId}/public")
            );
        });

        Event::listen(TenancyEnded::class, function () {
            if (!app()->runningInConsole()) {
                Config::set('session.cookie', config('session.cookie')); // ← من config الأصلي
                Config::set('session.path',   '/');
            }
        });
    }

    // public function boot()
    // {

    //     $this->bootEvents();

    //     $this->makeTenancyMiddlewareHighestPriority();

    //     Event::listen(TenancyInitialized::class, function () {
    //         $tenantId = tenant('id');
    //         Config::set('filesystems.disks.tenant_public.root', storage_path("app/tenants/{$tenantId}/public"));
    //         Config::set('filesystems.disks.tenant_public.url', url("tenants/{$tenantId}/public"));
    //     });

    //     // // --- أعده للوضع المركزي عند الخروج من سياق المستأجر ---
    //     // Event::listen(RevertedToCentralContext::class, function () {
    //     //     Config::set('filesystems.disks.tenant_public.root', storage_path('app/public'));
    //     //     Config::set('filesystems.disks.tenant_public.url', url('storage'));
    //     // });
    // }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }


    protected function mapRoutes() {}



    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [

            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            // Middleware\InitializeTenancyBySubdomain::class,
            // Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            // Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}
