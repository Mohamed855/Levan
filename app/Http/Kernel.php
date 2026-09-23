<?php

namespace App\Http;

use App\Http\Middleware\changeEnvAttributes;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

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
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // \Bepsvpt\SecureHeaders\SecureHeadersMiddleware::class,

        \App\Http\Middleware\SecurityHeadersMiddleware::class, /// شغله بعد الانتهاء من التعديلات

        changeEnvAttributes::class,

    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ],


        'admin' => [
            'web',
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
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
        'auth'              => \App\Http\Middleware\Authenticate::class,
        'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session'      => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'               => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm'  => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed'            => \App\Http\Middleware\ValidateSignature::class,
        'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // ADMIN MIDDLEWARE
        'admin.active'     => \App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin::class,  // هل المستخدم نشط ام لا
        'admin.2fa.force'  => \App\Http\Middleware\EnsureAdminTwoFactorIsEnforced::class, // فرض المصادقة الثنائية على السوبر أدمن


        // TENANT MIDDLEWARE
        'tenant.path'           => \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class, // عبر الـ path-based
        'tenant.domain'         => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,  // للـ subdomain-based

        'tenant.subscription'   => \App\Http\Middleware\Tenancy\CheckSubscription::class, // لتاكد من الاشتراك
        'tenant.license'        => \App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense::class, // للتأكد من وجود ترخيص نشط
        'tenant.maintenance'    => \App\Http\Middleware\Tenancy\TenantMaintenance::class, // وضع الصيانة للtenant
        'tenant.redirect'       => \App\Http\Middleware\Tenancy\TenantDomainRedirect::class, // إعادة التوجيه من Path إلى Domain إذا كان Domain مفعّل
        'tenant.2fa.force'      => \App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced::class, // فرض المصادقة الثنائية على مستخدمي المستأجر
        'prevent.central'       => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class, // منع الوصول من الدومينات المركزية

        'branches.limit'          => \App\Http\Middleware\Tenancy\CheckLimits\CheckBranchesLimits::class, // حد الفروع المسموح به
        'check.domain.management' => \App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement::class,
        'check.domain.balance'    => \App\Http\Middleware\CheckDomainBalance::class, // للتأكد من وجود رصيد كافي لتسجيل الدومين



        // TENANT API MIDDLEWARE
        'tenant.token'          => \App\Http\Middleware\Tenancy\VerifyTenantToken::class,  // انشاء توكن للtenant


    ];

    protected $routeMiddleware = [];
}
