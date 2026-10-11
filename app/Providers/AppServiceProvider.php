<?php

namespace App\Providers;

use App\Models\Tenants\Assignments\Assignment;
use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\shared\Payment\Gateways\MoyasarGateway;
use App\Services\shared\Tenant\TenantInitializer;
use App\Support\Vite as AppVite;
use Carbon\Carbon;
use Database\Seeders\tenant\ContactResultSeeder;
use Database\Seeders\tenant\CustomerStatusSeeder;
use Database\Seeders\tenant\GeneralSettingsSeeder;
use Database\Seeders\tenant\PermissionsRolesSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Vite;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Vite::class, AppVite::class);

        $this->app->bind(PaymentGatewayInterface::class, MoyasarGateway::class);

        $this->app->bind(TenantInitializer::class, function ($app) {
            return new TenantInitializer([
                PermissionsRolesSeeder::class,
                CustomerStatusSeeder::class,
                ContactResultSeeder::class,
                GeneralSettingsSeeder::class,
            ]);
        });
    }

    public function boot(): void
    {
        Relation::morphMap([
            'assignment' => Assignment::class,
        ]);

        $this->loadMigrationsFrom(database_path('migrations/admin'));

        Fortify::ignoreRoutes(); // مهم جدًا - عشان نسجلها احنا يدويًا داخل tenant group

        Blade::directive('cspNonce', function () {
            return "<?php echo request()->attributes->get('csp_nonce'); ?>";
        });

        Carbon::setLocale('ar');
        Paginator::useBootstrapFive();

        $cssClassResolver = function (?string $src, string $url, ?array $chunk, ?array $manifest): array {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src)
                        ? 'template-customizer-core-css'
                        : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src)
                            ? 'template-customizer-theme-css'
                            : ''),
                ];
            }

            return [];
        };

        ViteFacade::useStyleTagAttributes($cssClassResolver);
        ViteFacade::useScriptTagAttributes($cssClassResolver);
    }
}
