<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\changeEnvAttributes;
use App\Http\Middleware\CheckDomainBalance;
use App\Http\Middleware\DiscardCompletedWizardDraft;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement;
use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Http\Middleware\Tenancy\TenantDomainRedirect;
use App\Http\Middleware\Tenancy\TenantMaintenance;
use App\Http\Middleware\Tenancy\VerifyTenantToken;
use App\Http\Middleware\VerifyTenantUpgradeRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(changeEnvAttributes::class);
        $middleware->append(DiscardCompletedWizardDraft::class);

        $middleware->validateCsrfTokens(except: [
            'wizard-drafts/validate-step',
            '*/wizard-drafts/validate-step',
            'form-validation',
            '*/form-validation',
            'api/central/coupon/*',
        ]);

        // Middleware groups
        $middleware->group('admin', [
            'web',
        ]);

        // Middleware aliases
        $middleware->alias([
            // Laravel Core
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,

            // Admin Middleware
            'admin.active' => EnsureAuthenticatedAndActiveAdmin::class,
            'admin.2fa.force' => EnsureAdminTwoFactorIsEnforced::class,

            // Tenant Middleware
            'tenant.path' => InitializeTenancyByPath::class,
            'tenant.domain' => InitializeTenancyByDomain::class,
            'tenant.subscription' => CheckSubscription::class,
            'tenant.license' => EnsureUserHasActiveLicense::class,
            'tenant.maintenance' => TenantMaintenance::class,
            'tenant.redirect' => TenantDomainRedirect::class,
            'tenant.2fa.force' => EnsureTenantTwoFactorIsEnforced::class,
            'prevent.central' => PreventAccessFromCentralDomains::class,

            'check.domain.management' => CheckDomainManagement::class,
            'check.domain.balance' => CheckDomainBalance::class,

            // Tenant API Middleware
            'tenant.token' => VerifyTenantToken::class,
            'tenant.upgrade.signature' => VerifyTenantUpgradeRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $errorView = static fn ($request, int $status): string => "errors.{$status}";

        // Tenant identification failure -> 404
        $exceptions->render(function (TenantCouldNotBeIdentifiedException $e, $request) use ($errorView) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستأجر غير موجود',
                ], 404);
            }

            return response()->view($errorView($request, 404), ['exception' => $e], 404);
        });

        // Authorization exceptions -> 403
        $exceptions->render(function (AuthorizationException $e, $request) use ($errorView) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول',
                ], 403);
            }

            return response()->view($errorView($request, 403), ['exception' => $e], 403);
        });

        // HTTP Exceptions (403, 404, 500)
        $exceptions->render(function (HttpExceptionInterface $e, $request) use ($errorView) {
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

            $view = $errorView($request, $status);
            if (view()->exists($view)) {
                return response()->view($view, ['exception' => $e], $status);
            }

            if ($status >= 500) {
                return response()->view($errorView($request, 500), ['exception' => $e], $status);
            }
        });

        // Unhandled server exceptions -> 500
        $exceptions->render(function (Throwable $e, $request) use ($errorView) {
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ داخلي في الخادم',
                ], 500);
            }

            try {
                return response()->view($errorView($request, 500), ['exception' => $e], 500);
            } catch (Throwable) {
                return null;
            }
        });
    })->create();
