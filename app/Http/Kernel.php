<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\changeEnvAttributes;
use App\Http\Middleware\CheckDomainBalance;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement;
use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Http\Middleware\Tenancy\TenantDomainRedirect;
use App\Http\Middleware\Tenancy\TenantMaintenance;
use App\Http\Middleware\Tenancy\VerifyTenantToken;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        // \Bepsvpt\SecureHeaders\SecureHeadersMiddleware::class,

        SecurityHeadersMiddleware::class, // / شغله بعد الانتهاء من التعديلات

        changeEnvAttributes::class,

    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,

        ],

        'admin' => [
            'web',
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // Laravel Core Middleware
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,

        // ADMIN MIDDLEWARE
        'admin.active' => EnsureAuthenticatedAndActiveAdmin::class,  // هل المستخدم نشط ام لا
        'admin.2fa.force' => EnsureAdminTwoFactorIsEnforced::class, // فرض المصادقة الثنائية على السوبر أدمن

        // TENANT MIDDLEWARE
        'tenant.path' => InitializeTenancyByPath::class, // عبر الـ path-based
        'tenant.domain' => InitializeTenancyByDomain::class,  // للـ subdomain-based

        'tenant.subscriptions' => CheckSubscription::class, // لتاكد من الاشتراك
        'tenant.license' => EnsureUserHasActiveLicense::class, // للتأكد من وجود ترخيص نشط
        'tenant.maintenance' => TenantMaintenance::class, // وضع الصيانة للtenant
        'tenant.redirect' => TenantDomainRedirect::class, // إعادة التوجيه من Path إلى Domain إذا كان Domain مفعّل
        'tenant.2fa.force' => EnsureTenantTwoFactorIsEnforced::class, // فرض المصادقة الثنائية على مستخدمي المستأجر
        'prevent.central' => PreventAccessFromCentralDomains::class, // منع الوصول من الدومينات المركزية

        'check.domain.management' => CheckDomainManagement::class,
        'check.domain.balance' => CheckDomainBalance::class, // للتأكد من وجود رصيد كافي لتسجيل الدومين

        // TENANT API MIDDLEWARE
        'tenant.token' => VerifyTenantToken::class,  // انشاء توكن للtenant

    ];

    protected $routeMiddleware = [];
}
