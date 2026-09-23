<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public static function home(): string
    {
        if (! tenant()) {
            return '/';
        }

        $host = request()->getHost();

        $centralDomains = array_map(
            fn($d) => str_replace(['http://', 'https://', 'www.'], '', trim($d)),
            config('tenancy.central_domains', [])
        );

        $isCentralDomain = in_array($host, $centralDomains, true) || in_array('www.' . $host, $centralDomains, true);

        return $isCentralDomain ? '/' . tenant('id') . '/dashboard' : '/dashboard';
    }

    public const ADMIN = '/admin';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            $this->mapSiteRoutes();
            $this->mapAdminRoutes();
            $this->mapTenantRoutes();
            $this->mapApiRoutes();
        });
    }

    // Main Site Routes (Landing, Trial, Subscription)
    protected function mapSiteRoutes(): void
    {
        foreach (config('tenancy.central_domains') as $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            Route::middleware(['web'])->domain($cleanDomain)->group(base_path('routes/site/site.php'));
        }
    }

    // Admin Panel Routes
    protected function mapAdminRoutes(): void
    {
        foreach (config('tenancy.central_domains') as $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            Route::middleware(['web'])->domain($cleanDomain)->group(base_path('routes/admin/admin.php'));
        }
    }

    // Tenant-Specific Routes
    protected function mapTenantRoutes(): void
    {
        foreach (config('tenancy.central_domains') as $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            Route::middleware([
                'web',
                "tenant.path",
                'tenant.redirect',
                'tenant.maintenance',
            ])
                ->domain($cleanDomain)
                ->prefix('{tenant}')
                ->name('path.')
                ->where(['tenant' => '^(?!admin$)[^/]+$'])
                ->group(base_path('routes/tenant/tenant.php'));
        }

        Route::middleware([
            'web',
            "tenant.domain",
            "prevent.central",
            'tenant.maintenance',
        ])->name('domain.')->group(base_path('routes/tenant/tenant.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
    }
}