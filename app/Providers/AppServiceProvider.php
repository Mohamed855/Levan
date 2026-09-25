<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Laravel\Fortify\Fortify;

use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\shared\Payment\Gateways\MoyasarGateway;
use App\Services\shared\Tenant\TenantInitializer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, MoyasarGateway::class);

        $this->app->bind(TenantInitializer::class, function ($app) {
            return new TenantInitializer([
                \Database\Seeders\tenant\PermissionsRolesSeeder::class,
                \Database\Seeders\tenant\CustomerStatusSeeder::class,
                \Database\Seeders\tenant\ContactResultSeeder::class,
                \Database\Seeders\tenant\GeneralSettingsSeeder::class,
                \Database\Seeders\tenant\BranchSeeder::class,
            ]);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/admin'));

        Fortify::ignoreRoutes(); // مهم جدًا - عشان نسجلها احنا يدويًا داخل tenant group

        Blade::directive('cspNonce', function () {
            return "<?php echo request()->attributes->get('csp_nonce'); ?>";
        });

        Carbon::setLocale('ar');
        Paginator::useBootstrapFive();

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src)
                        ? 'template-customizer-core-css'
                        : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src)
                            ? 'template-customizer-theme-css'
                            : '')
                ];
            }
            return [];
        });
    }
}
