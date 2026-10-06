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

        RateLimiter::for('tenant-upgrade', function (Request $request) {
            $limit = min(max((int) config('app.upgrade_api_rate_limit', 5), 1), 60);
            $tenantId = (string) $request->route('tenantId');

            return Limit::perMinute($limit)->by(hash('sha256', $tenantId . '|' . $request->ip()));
        });

        $this->routes(function () {
            $this->mapSiteRoutes();
            $this->mapAdminRoutes();
            $this->mapApiRoutes();
            $this->mapTenantRoutes();
        });
    }

    // Main Site Routes (Landing, Trial, Subscription)
    protected function mapSiteRoutes(): void
    {
        foreach ($this->centralDomains() as $index => $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            $routes = Route::middleware(['web'])->domain($cleanDomain);
            if ($index > 0) {
                $routes->as("central-domain-{$index}.");
            }

            $routes->group(base_path('routes/site/site.php'));
        }
    }

    // Admin Panel Routes
    protected function mapAdminRoutes(): void
    {
        foreach ($this->centralDomains() as $index => $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            $routes = Route::middleware(['web'])->domain($cleanDomain);
            if ($index > 0) {
                $routes->as("central-domain-{$index}.");
            }

            $routes->group(base_path('routes/admin/admin.php'));
        }
    }

    // Tenant-Specific Routes
    protected function mapTenantRoutes(): void
    {
        foreach ($this->centralDomains() as $index => $domain) {
            $cleanDomain = str_replace(['http://', 'https://'], '', $domain);

            Route::middleware([
                'web',
                "tenant.path",
                'tenant.redirect',
                'tenant.maintenance',
            ])
                ->domain($cleanDomain)
                ->prefix('{tenant}')
                ->name($index === 0 ? 'path.' : "central-domain-{$index}.path.")
                ->where(['tenant' => '^(?!(?i)(admin|api)(?:\/|$))[^/]+$'])
                ->group(base_path('routes/tenant/tenant.php'));
        }

        Route::middleware([
            'web',
            "prevent.central",
            "tenant.domain",
            'tenant.maintenance',
        ])->name('domain.')->group(base_path('routes/tenant/tenant.php'));
    }

    /**
     * Only the primary central domain owns the canonical route names. Additional
     * development/alias domains receive unique route-name prefixes so route:cache
     * remains serializable while inbound requests keep working on every domain.
     *
     * @return list<string>
     */
    private function centralDomains(): array
    {
        return array_values(array_unique(array_filter(
            config('tenancy.central_domains', []),
            static fn (mixed $domain): bool => is_string($domain) && $domain !== '',
        )));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
    }
}
