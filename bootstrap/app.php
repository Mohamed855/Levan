<?php

use App\Http\Middleware\changeEnvAttributes;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(changeEnvAttributes::class);
        $middleware->append(\App\Http\Middleware\DiscardCompletedWizardDraft::class);

        // Middleware groups
        $middleware->group('admin', [
            'web',
        ]);

        // Middleware aliases
        $middleware->alias([
            // Laravel Core
            'auth'              => \App\Http\Middleware\Authenticate::class,
            'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,

            // Admin Middleware
            'admin.active'     => \App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin::class,
            'admin.2fa.force'  => \App\Http\Middleware\EnsureAdminTwoFactorIsEnforced::class,

            // Tenant Middleware
            'tenant.path'           => \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class,
            'tenant.domain'         => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            'tenant.subscription'   => \App\Http\Middleware\Tenancy\CheckSubscription::class,
            'tenant.license'        => \App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense::class,
            'tenant.maintenance'    => \App\Http\Middleware\Tenancy\TenantMaintenance::class,
            'tenant.redirect'       => \App\Http\Middleware\Tenancy\TenantDomainRedirect::class,
            'tenant.2fa.force'      => \App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced::class,
            'prevent.central'       => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,

            'check.domain.management' => \App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement::class,
            'check.domain.balance'    => \App\Http\Middleware\CheckDomainBalance::class,

            // Tenant API Middleware
            'tenant.token'          => \App\Http\Middleware\Tenancy\VerifyTenantToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tenant identification failure -> 404
        $exceptions->render(function (\Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException $e, $request) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستأجر غير موجود',
                ], 404);
            }

            return response()->view('tenants.errors.404', ['exception' => $e], 404);
        });

        // Authorization exceptions -> 403
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول',
                ], 403);
            }

            return response()->view('tenants.errors.403', ['exception' => $e], 403);
        });

        // HTTP Exceptions (403, 404, 500)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            $status = $e->getStatusCode();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: match ($status) {
                        403 => 'غير مصرح لك بالوصول',
                        404 => 'الصفحة غير موجودة',
                        500 => 'حدث خطأ داخلي في الخادم',
                        default => 'حدث خطأ ما',
                    },
                ], $status);
            }

            if (view()->exists("tenants.errors.{$status}")) {
                return response()->view("tenants.errors.{$status}", ['exception' => $e], $status);
            }

            if ($status >= 500) {
                return response()->view('tenants.errors.500', ['exception' => $e], $status);
            }
        });

        // Unhandled server exceptions -> 500
        $exceptions->render(function (\Throwable $e, $request) {
            if ($e instanceof \Illuminate\Validation\ValidationException || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'حدث خطأ داخلي في الخادم',
                ], 500);
            }

            try {
                return response()->view('tenants.errors.500', ['exception' => $e], 500);
            } catch (\Throwable) {
                return null;
            }
        });
    })->create();
