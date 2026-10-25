<?php

declare(strict_types=1);

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\activity_log\ActivityLogController;
use App\Http\Controllers\Tenants\Archive\Assignment\AssignmentArchiveController;
use App\Http\Controllers\Tenants\Archive\Auction\AuctionArchiveController;
use App\Http\Controllers\Tenants\Archive\Company\CompanyArchiveController;
use App\Http\Controllers\Tenants\Archive\Property\PropertyArchiveController;
use App\Http\Controllers\Tenants\Archive\User\UserArchiveController;
use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorSettingsController;
use App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenants\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Tenants\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Tenants\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorChallengeController;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use App\Http\Controllers\Tenants\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenants\Auth\RegisteredUserController;
use App\Http\Controllers\Tenants\Auth\VerifyEmailController;
use App\Http\Controllers\Tenants\Auth\PasswordController;
use App\Http\Controllers\Tenants\Bills\BillsController;
use App\Http\Controllers\Tenants\BusinessCenter\Company\CompanyController;
use App\Http\Controllers\Tenants\ChartSetting\ChartSettingController;
use App\Http\Controllers\Tenants\Dashboard\DashboardController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use App\Http\Controllers\Tenants\Domains\CheckMultipleDomainController as DomainsCheckMultipleDomainController;
use App\Http\Controllers\Tenants\Domains\CpanelController;
use App\Http\Controllers\Tenants\Domains\DomainController;
use App\Http\Controllers\Tenants\Domains\DomainLookupController;
use App\Http\Controllers\Tenants\Domains\DomainPaymentController;
use App\Http\Controllers\Tenants\Domains\GetDomainInfoController;
use App\Http\Controllers\Tenants\Domains\RenewalDomainController;
use App\Http\Controllers\Tenants\License\LicenseController;
use App\Http\Controllers\Tenants\Notification\NotificationController;
use App\Http\Controllers\Tenants\Properties\PropertiesController;
use App\Http\Controllers\Tenants\Settings\ApiTokenSettingsController;
use App\Http\Controllers\Tenants\Settings\SettingsController;
use App\Http\Controllers\Tenants\Subscriptions\NewSubscriptionController;
use App\Http\Controllers\Tenants\Subscriptions\TenantSubscriptionController;
use App\Http\Controllers\Tenants\Tickets\TicketController;
use App\Http\Controllers\Tenants\UserMangment\Role\RoleController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|============================================================================
| Tenant Routes (Phase 1: Cleaned & Streamlined for Auction & Real Estate)
|============================================================================
*/

Route::middleware(['auth:tenant', 'tenant.2fa.force'])->group(function () {
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function () {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::get('file', [WizardDraftController::class, 'showFile'])->name('file.show');
        Route::match(['post', 'put', 'patch'], '/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
        Route::delete('file', [WizardDraftController::class, 'destroyFile'])->name('file.destroy');
        Route::match(['post', 'put', 'patch'], 'validate-step', [WizardDraftController::class, 'validateStep'])
            ->defaults('shared_form_validation_actions', [
                UserController::class.'@store',
                UserController::class.'@update',
                UserController::class.'@updateImage',
                UserController::class.'@updateBackgroundImage',
                UserController::class.'@updatePassword',
                RoleController::class.'@store',
                RoleController::class.'@update',
                SettingsController::class.'@update',
                CompanyController::class.'@store',
                CompanyController::class.'@update',
                AssignmentsController::class.'@store',
                AssignmentsController::class.'@update',
                PropertiesController::class.'@store',
                PropertiesController::class.'@update',
                AuctionsController::class.'@store',
                AuctionsController::class.'@update',
                TicketController::class.'@store',
                TicketController::class.'@reply',
                TicketController::class.'@rateReply',
                TicketController::class.'@submitSurvey',
                BillsController::class.'@uploadReceipt',
                BillsController::class.'@processCheckout',
                TenantSubscriptionController::class.'@processRenew',
                TenantSubscriptionController::class.'@processLicense',
                NewSubscriptionController::class.'@checkoutStore',
                TenantSubscriptionController::class.'@licenseCheckout',
                TenantTwoFactorSettingsController::class.'@confirm',
            ])
            ->name('validate-step');
    });

    // الاشتراك والترقية
    Route::prefix('subscribe')->name('subscribe.')->group(function () {
        Route::name('index')->get('/', [NewSubscriptionController::class, 'index']);
        Route::name('checkout')->get('plan/{plan}/checkout', [NewSubscriptionController::class, 'checkout']);
        Route::name('checkout.store')->post('checkout', [NewSubscriptionController::class, 'checkoutStore'])
            ->defaults('shared_form_validation', true);
        Route::name('callback')->get('callback', [NewSubscriptionController::class, 'handleNewSubscriptionCallback']);
    });

    // الفواتير
    Route::prefix('bills')->name('bills.')->group(function () {
        Route::name('index')->get('/', [BillsController::class, 'index']);
        Route::name('callback')->get('callback', [BillsController::class, 'callback']);
        Route::name('show')->get('{id}', [BillsController::class, 'show']);
        Route::name('checkout')->get('{id}/checkout', [BillsController::class, 'checkout']);
        Route::name('checkout.process')->post('{id}/checkout', [BillsController::class, 'processCheckout'])
            ->defaults('shared_form_validation', true);

        // مسارات التحويل البنكي
        Route::name('bank-transfer')->get('{id}/bank-transfer', [BillsController::class, 'bankTransfer']);
        Route::name('upload-receipt')->post('{id}/upload-receipt', [BillsController::class, 'uploadReceipt'])
            ->defaults('shared_form_validation', true);
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::name('index')->get('/', [TenantSubscriptionController::class, 'index']);
    });

    Route::middleware('tenant.license')->group(function () {
        // الاشتراكات والتجديد
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::prefix('renew')->name('renew.')->group(function () {
                Route::get('/', [TenantSubscriptionController::class, 'renewCheckout'])->name('checkout');
                Route::post('/', [TenantSubscriptionController::class, 'processRenew'])
                    ->defaults('shared_form_validation', true)
                    ->name('process');
                Route::get('/callback', [TenantSubscriptionController::class, 'renewCallback'])->name('callback');
            });

            // شراء تراخيص إضافية
            Route::prefix('licenses')->name('licenses.')->group(function () {
                Route::get('/', [TenantSubscriptionController::class, 'licenseCheckout'])
                    ->defaults('shared_form_validation', true)
                    ->name('checkout');
                Route::post('/', [TenantSubscriptionController::class, 'processLicense'])
                    ->defaults('shared_form_validation', true)
                    ->name('process');
                Route::get('/callback', [TenantSubscriptionController::class, 'licenseCallback'])->name('callback');
            });
        });
    });

    // المسارات المحمية باشتراك وترخيص نشط
    Route::middleware('tenant.subscription', 'tenant.license')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | الدعم الفني (Tickets) - محفوظ ويعمل بكامل كفاءته
        |--------------------------------------------------------------------------
        */
        Route::name('tickets.')->prefix('tickets')->group(function () {
            Route::resource('/', TicketController::class)->parameters(['' => 'ticket'])->only(['index', 'show', 'create', 'store']);
            Route::name('reply')->post('/reply/{ticket}', [TicketController::class, 'reply'])
                ->defaults('shared_form_validation', true);

            // تقييم الرد
            Route::name('replies.rate')->post('{ticket}/replies/{reply}/rate', [TicketController::class, 'rateReply'])
                ->defaults('shared_form_validation', true);

            // التقييم النهائي للتذكرة
            Route::name('survey.submit')->post('{ticket}/survey', [TicketController::class, 'submitSurvey'])
                ->defaults('shared_form_validation', true);
        });

        Route::prefix('chartSetting')->name('charSetting.')->group(function () {
            Route::name('update')->post('/dashboard/chart-visibility', [ChartSettingController::class, 'updateChartVisibility']);
        });

        /*
        |--------------------------------------------------------------------------
        | إعدادات النطاقات (Domains)
        |--------------------------------------------------------------------------
        */
        Route::prefix('domains')->name('domains.')->group(function () {
            Route::name('setting')->get('setting', [DomainController::class, 'domainSetting']);
            Route::name('newdomain')->get('newdomain', [DomainController::class, 'newdomain']);
            Route::name('link-domain')->get('link-domain', [DomainController::class, 'linkDomain']);
            Route::name('link-sub-domain')->get('link-sub-domain', [DomainController::class, 'linkSubDomain']);
            Route::name('faq')->get('faq', [DomainController::class, 'faq']);
            Route::name('explain')->get('explain', [DomainController::class, 'explain']);
            Route::name('subdomainblade')->get('subdomain', [DomainController::class, 'subdomainblade']);

            Route::name('get-domain-info')->get('getInfo/{domain}', [GetDomainInfoController::class, 'getDomainInfo']);

            Route::name('payment.renewal')->match(['post', 'get'], 'renewal/payment', [RenewalDomainController::class, 'renewalDomainBlade']);
            Route::name('renewal')->post('renewal', [RenewalDomainController::class, 'renewalDomain']);

            Route::name('moyasar.renewal.callback')->get('renewal/payment/domain/callback', [DomainPaymentController::class, 'renewalCallback']);
            Route::name('moyasar.renewal')->post('renewal/payment/domain', [DomainPaymentController::class, 'startRenewal']);

            Route::name('check')->get('/check/{domain}', [DomainsCheckMultipleDomainController::class, 'checkSingle'])
                ->where('domain', '[a-zA-Z0-9.-]+');

            Route::post('/check-multiple', [DomainsCheckMultipleDomainController::class, 'checkMultiple'])
                ->name('check-multiple');

            Route::get('/suggest-alternatives', [DomainsCheckMultipleDomainController::class, 'suggestAlternatives'])
                ->name('suggest-alternatives');

            Route::get('/suggest-names', [DomainsCheckMultipleDomainController::class, 'suggestNames'])
                ->name('suggest-names');

            Route::delete('/cache/{domain}', [DomainsCheckMultipleDomainController::class, 'clearCache'])
                ->name('cache.clear')
                ->where('domain', '[a-zA-Z0-9.-]+');

            Route::delete('/cache', [DomainsCheckMultipleDomainController::class, 'clearAllCache'])
                ->name('cache.clear-all');

            Route::name('apilayer')->match(['get', 'post'], 'check-domain/{domain}', [DomainLookupController::class, 'checkAvailability']);
            Route::name('check-domain')->match(['get', 'post'], 'check-domainss/{domain}', [DomainLookupController::class, 'inspectDns']);
            Route::name('checkSubDomain')->get('checkSubdomain/{subdomain}', [DomainLookupController::class, 'checkSubDomain']);

            Route::name('payment.new')->post('payment', [DomainPaymentController::class, 'startRegistration'])->middleware('check.domain.balance');
            Route::name('payment.callbacknewdomain')->get('payment/callbacknewDomain', [DomainPaymentController::class, 'registrationCallback']);
            Route::name('process-transfer-payment')->post('process-transfer-payment', [DomainPaymentController::class, 'startTransfer']);
            Route::name('payment.callback.transfer')->get('process-transfer-payment-callback', [DomainPaymentController::class, 'transferCallback']);

            Route::name('checkExistTransferDomain')->post('checkExistTransferDomain/{domain}', [DomainLookupController::class, 'checkTransferAvailability']);

            Route::name('linkDomainproccess')->post('link-domain-proccess', [DomainController::class, 'setupdnsForDomain']);
            Route::post('/verify-dns', [DomainController::class, 'verifyDNS'])->name('domains.verify-dns');
            Route::name('check-domain-exists')->post('check-domain-exists', [DomainController::class, 'checkDomainExists']);
            Route::name('switch.active')->post('/switch-active', [DomainController::class, 'switchActiveDomain']);
            Route::name('modifyaccount')->post('modifyaccount', [DomainController::class, 'modifyaccount']);
            Route::name('testverify')->get('verifydns/{domain}/{method}', [DomainController::class, 'verifyDNSTest']);

            Route::name('check.balance')->get('/check-balance', [DomainController::class, 'getBalance']);
            Route::name('get.info.domain')->get('/get-domain-info/{domain}', [GetDomainInfoController::class, 'getDomainInfo']);
            Route::name('get.result')->post('/check-domain-result/{domain}', [DomainController::class, 'calculateResults']);

            Route::name('cpanel.limits')->get('check-cpanel-limits', [CpanelController::class, 'checkLimits']);
        });

        // التراخيص
        Route::prefix('licenses')->name('licenses.')->group(function () {
            Route::name('assign')->post('assign', [LicenseController::class, 'assign']);
            Route::name('release')->post('release', [LicenseController::class, 'release']);
        });

        Route::get('/', function () {
            return redirect(tenant_route_name('dashboard'));
        });

        // لوحة القيادة
        Route::prefix('dashboard')->middleware(['verified'])->group(function () {
            Route::name('dashboard')->get('/', [DashboardController::class, 'index']);
            Route::name('dashboard.statistics')->get('/statistics', [DashboardController::class, 'getStatisticsData']);
        });

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/all', [NotificationController::class, 'allPage'])->name('all');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::post('/mass-delete', [NotificationController::class, 'massDelete'])->name('mass-delete');
            Route::resource('/', NotificationController::class)
                ->only(['index', 'destroy'])
                ->parameters(['' => 'id']);
        });

        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')->name('users.')->group(function () {
            Route::name('profile')->get('profile', [UserController::class, 'showProfile']);
            Route::name('update-image')->post('{user}/image', [UserController::class, 'updateImage'])->defaults('shared_form_validation', true);
            Route::name('update-background')->post('{user}/background', [UserController::class, 'updateBackgroundImage'])->defaults('shared_form_validation', true);
            Route::name('toggle-status')->patch('{user}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::name('password.change')->get('password/change', [UserController::class, 'showChangePasswordForm']);
            Route::name('password.update')->post('password/change', [UserController::class, 'updatePassword'])->defaults('shared_form_validation', true);
            Route::resource('/', UserController::class)->parameters(['' => 'user']);
        });

        /*
        |--------------------------------------------------------------------------
        | Roles & Permissions
        |--------------------------------------------------------------------------
        */
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::name('user.get')->get('users/{id}/get-roles', [RoleController::class, 'getUserRoles']);
            Route::name('user.update')->post('users/{id}/update-roles', [RoleController::class, 'updateUserRoles']);
            Route::name('user.status')->patch('users/{userId}/status', [RoleController::class, 'updateStatus']);
            Route::resource('/', RoleController::class)->parameters(['' => 'role']);
        });

        /*
        |--------------------------------------------------------------------------
        | Assignments Management (الإسنادات)
        |--------------------------------------------------------------------------
        */
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::name('selected-properties')->get('selected-properties', [AssignmentsController::class, 'selectedProperties']);
            Route::name('properties')->get('{assignment}/properties', [AssignmentsController::class, 'getProperties']);
            Route::name('toggle-status')->patch('{assignment}/toggle-status', [AssignmentsController::class, 'toggleStatus']);
        });
        Route::resource('assignments', AssignmentsController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Properties Management (العقارات)
        |--------------------------------------------------------------------------
        */
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::name('toggle-status')->patch('{property}/toggle-status', [PropertiesController::class, 'toggleStatus']);
        });
        Route::resource('properties', PropertiesController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Auctions Management (المزادات ومخرجاتها)
        |--------------------------------------------------------------------------
        */
        Route::prefix('auctions')->name('auctions.')->group(function () {
            Route::name('toggle-status')->patch('{auction}/toggle-status', [AuctionsController::class, 'toggleStatus']);
            Route::prefix('{auction}/reports')->name('reports.')->group(function () {
                Route::name('status')->get('status', [AuctionReportController::class, 'status']);
                Route::name('download')->get('download/{type}', [AuctionReportController::class, 'download']);
                Route::name('retry')->post('retry', [AuctionReportController::class, 'retry']);
            });
        });
        Route::resource('auctions', AuctionsController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Company Management (الشركات / العملاء)
        |--------------------------------------------------------------------------
        */
        Route::prefix('companies')->name('companies.')->group(function () {
            Route::name('index')->get('/', [CompanyController::class, 'index']);
            Route::name('create')->get('create', [CompanyController::class, 'create']);
            Route::name('store')->post('/', [CompanyController::class, 'store']);
            Route::name('show')->get('{company}', [CompanyController::class, 'show']);
            Route::name('edit')->get('{company}/edit', [CompanyController::class, 'edit']);
            Route::name('update')->put('{company}', [CompanyController::class, 'update']);
            Route::name('destroy')->delete('{company}', [CompanyController::class, 'destroy']);
            Route::name('toggle-status')->patch('{company}/toggle-status', [CompanyController::class, 'toggleStatus']);
        });

        /*
        |--------------------------------------------------------------------------
        | Settings & API Tokens
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::name('index')->get('/', [SettingsController::class, 'index']);
            Route::name('update')->put('/', [SettingsController::class, 'update']);
            Route::name('center')->get('/center', [SettingsController::class, 'center']);
        });

        Route::prefix('api-token-settings')->name('api-token-settings.')->group(function () {
            Route::name('index')->get('/', [ApiTokenSettingsController::class, 'index']);
            Route::name('regenerate')->post('/regenerate', [ApiTokenSettingsController::class, 'regenerate']);
        });

        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::name('index')->get('/', [ActivityLogController::class, 'index']);
            Route::name('archives')->get('archives', [ActivityLogController::class, 'archives']);
            Route::name('get-all-ids')->get('get-all-ids', [ActivityLogController::class, 'getAllIds']);
            Route::name('export')->post('export', [ActivityLogController::class, 'export']);
            Route::name('export-download')->get('export-download/{file}', [ActivityLogController::class, 'downloadExportFile']);
            Route::name('show')->get('{id}', [ActivityLogController::class, 'show']);
        });

        /*
        |--------------------------------------------------------------------------
        | Archive (البيانات المحذوفة)
        |--------------------------------------------------------------------------
        */
        Route::prefix('archive')->name('archive.')->group(function () {
            // Company Archive
            Route::prefix('companies')->name('companies.')->group(function () {
                Route::name('index')->get('/', [CompanyArchiveController::class, 'index']);
                Route::name('restore')->patch('{company}/restore', [CompanyArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{company}/force-delete', [CompanyArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [CompanyArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [CompanyArchiveController::class, 'massForceDelete']);
            });

            // User Archive
            Route::prefix('users')->name('users.')->group(function () {
                Route::name('index')->get('/', [UserArchiveController::class, 'index']);
                Route::name('restore')->patch('{user}/restore', [UserArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{user}/force-delete', [UserArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [UserArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [UserArchiveController::class, 'massForceDelete']);
            });

            // Assignment Archive
            Route::prefix('assignments')->name('assignments.')->group(function () {
                Route::name('index')->get('/', [AssignmentArchiveController::class, 'index']);
                Route::name('restore')->patch('{assignment}/restore', [AssignmentArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{assignment}/force-delete', [AssignmentArchiveController::class, 'forceDelete']);
            });

            // Property Archive
            Route::prefix('properties')->name('properties.')->group(function () {
                Route::name('index')->get('/', [PropertyArchiveController::class, 'index']);
                Route::name('restore')->patch('{property}/restore', [PropertyArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{property}/force-delete', [PropertyArchiveController::class, 'forceDelete']);
            });

            // Auction Archive
            Route::prefix('auctions')->name('auctions.')->group(function () {
                Route::name('index')->get('/', [AuctionArchiveController::class, 'index']);
                Route::name('restore')->patch('{auction}/restore', [AuctionArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{auction}/force-delete', [AuctionArchiveController::class, 'forceDelete']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Bulk Delete Route
        |--------------------------------------------------------------------------
        */
        Route::name('mass-delete')->post('/mass-delete', [MassDeleteController::class, 'massDelete']);
    });
});

/*
|--------------------------------------------------------------------------
| Authentication: Password Reset & Change
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->prefix('forgot-password')->group(function () {
    Route::name('password.request')->get('/', [PasswordResetLinkController::class, 'create']);
    Route::name('password.email')->post('/', [PasswordResetLinkController::class, 'store']);
});

Route::get('impersonate', [AuthenticatedSessionController::class, 'impersonate'])
    ->name('impersonate');

Route::middleware('guest:tenant')->group(function () {
    Route::match(['post', 'put', 'patch'], 'form-validation', [WizardDraftController::class, 'validateStep'])
        ->defaults('shared_form_validation_actions', [
            AuthenticatedSessionController::class . '@store',
            RegisteredUserController::class . '@store',
            PasswordResetLinkController::class . '@store',
            NewPasswordController::class . '@store',
            TenantTwoFactorChallengeController::class . '@store',
        ])
        ->name('form-validation');

    Route::name('register')->get('register', [RegisteredUserController::class, 'create']);
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::name('login')->get('login', [AuthenticatedSessionController::class, 'create']);
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::name('password.request')->get('forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::name('password.email')->post('forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::name('password.reset')->get('reset-password/{token}', [NewPasswordController::class, 'create']);
    Route::name('password.store')->post('reset-password', [NewPasswordController::class, 'store']);

    Route::get('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'create'])->name('tenants.two-factor.login');
    Route::post('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'store'])->name('tenants.two-factor.store')->middleware('throttle:5,1');
    Route::get('two-factor-challenge/cancel', [TenantTwoFactorChallengeController::class, 'cancel'])->name('tenants.two-factor.cancel');
});

Route::middleware('auth:tenant')->group(function () {
    Route::name('verification.notice')->get('verify-email', EmailVerificationPromptController::class);
    Route::name('verification.verify')->get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1']);
    Route::name('verification.send')->post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1');
    Route::name('password.confirm')->get('confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::name('password.update')->put('password', [PasswordController::class, 'update']);
    Route::name('logout')->post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::name('tenants.two-factor.')->group(function () {
        Route::get('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'show'])->name('index');
        Route::post('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'enable'])->name('enable');
        Route::delete('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'disable'])->name('disable');
        Route::post('confirmed-two-factor-authentication', [TenantTwoFactorSettingsController::class, 'confirm'])
            ->defaults('shared_form_validation', true)
            ->name('confirm');
        Route::get('two-factor-qr-code', [TenantTwoFactorSettingsController::class, 'qrCode'])->name('qr-code');
        Route::post('two-factor-recovery-codes', [TenantTwoFactorSettingsController::class, 'recoveryCodes'])->name('recovery-codes');
    });
});
