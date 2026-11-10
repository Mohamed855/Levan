<?php

namespace App\Providers;

use App\Models\Tenants\Assignments\Assignment;
use App\Services\Shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Shared\Payment\Gateways\MoyasarGateway;
use App\Services\Shared\Tenant\TenantInitializer;
use App\Support\Vite as AppVite;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\tenant\PermissionsRolesSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Vite;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! function_exists('system_date_format')) {
            $dateHelper = app_path('Helpers/DateHelper.php');
            if (is_file($dateHelper)) {
                require_once $dateHelper;
            }
        }

        $this->app->singleton(Vite::class, AppVite::class);

        $this->app->bind(PaymentGatewayInterface::class, MoyasarGateway::class);

        $this->app->bind(
            \App\Contracts\Auctions\BrowserDocumentRenderer::class,
            \App\Services\Tenants\Auctions\BrowsershotDocumentRenderer::class,
        );

        $this->app->bind(TenantInitializer::class, function ($app) {
            return new TenantInitializer([
                PermissionsRolesSeeder::class,
            ]);
        });
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)
            ->rules(function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value)
                    || ! preg_match('/[A-Z]/', $value)
                    || ! preg_match('/\pN/u', $value)
                    || ! preg_match('/\p{Z}|\p{S}|\p{P}/u', $value)) {
                    $fail('يجب ألا تقل كلمة المرور عن 8 أحرف (منهم حرف كبير ، رمز و رقم واحد علي الأقل)..');
                }
            }));

        if (PHP_OS_FAMILY === 'Windows' && empty(ini_get('openssl.cafile'))) {
            $caPath = storage_path('certificates/cacert.pem');
            if (file_exists($caPath)) {
                @ini_set('openssl.cafile', $caPath);
                @ini_set('curl.cainfo', $caPath);
            }
        }

        Relation::morphMap([
            'assignment' => Assignment::class,
        ]);

        if ($this->app->runningInConsole() && class_exists(\Illuminate\Foundation\Console\ServeCommand::class)) {
            $windowsVars = [
                'SystemRoot', 'SystemDrive', 'windir', 'ComSpec', 'PATHEXT',
                'TEMP', 'TMP', 'USERPROFILE', 'LOCALAPPDATA', 'APPDATA',
                'ProgramData', 'ProgramFiles', 'ProgramFiles(x86)',
            ];

            $allAllowed = array_merge(\Illuminate\Foundation\Console\ServeCommand::$passthroughVariables, $windowsVars);
            $matchedEnvKeys = [];

            foreach (array_keys($_ENV) as $key) {
                foreach ($allAllowed as $allowed) {
                    if (strcasecmp($key, $allowed) === 0) {
                        $matchedEnvKeys[] = $key;
                        break;
                    }
                }
            }

            \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables = array_values(array_unique(array_merge(
                \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables,
                $windowsVars,
                $matchedEnvKeys
            )));
        }

        $this->loadMigrationsFrom(database_path('migrations/admin'));

        Fortify::ignoreRoutes(); // مهم جدًا - عشان نسجلها احنا يدويًا داخل tenant group

        Blade::directive('cspNonce', function () {
            return "<?php echo request()->attributes->get('csp_nonce'); ?>";
        });

        Blade::directive('date', function (string $expression) {
            return "<?php echo e(system_date({$expression})); ?>";
        });

        Blade::directive('datetime', function (string $expression) {
            return "<?php echo e(system_datetime({$expression})); ?>";
        });

        Carbon::setLocale('ar');
        Carbon::setToStringFormat(system_date_format());
        CarbonImmutable::setToStringFormat(system_date_format());
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
