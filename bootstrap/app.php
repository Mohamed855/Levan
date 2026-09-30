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

            'branches.limit'          => \App\Http\Middleware\Tenancy\CheckLimits\CheckBranchesLimits::class,
            'check.domain.management' => \App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement::class,
            'check.domain.balance'    => \App\Http\Middleware\CheckDomainBalance::class,

            // Tenant API Middleware
            'tenant.token'          => \App\Http\Middleware\Tenancy\VerifyTenantToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
