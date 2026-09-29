<?php

use App\Http\Controllers\Admin\APILayer\APILayerController;
use App\Http\Controllers\Admin\Payments\Moyaser\MoyaserController;
use App\Http\Controllers\Tenants\__LinkDomain\LinkDomainController;
use App\Http\Controllers\Tenants\activity_log\ActivityLogController;
use App\Http\Controllers\Tenants\Archive\User\UserArchiveController;
use App\Http\Controllers\Tenants\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenants\Bills\BillsController;
use App\Http\Controllers\Tenants\Branch\BranchController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageIndividualLogController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageLogController;
use App\Http\Controllers\Tenants\Dashboard\DashboardController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use App\Http\Controllers\Tenants\Domains\CheckMultipleDomainController as DomainsCheckMultipleDomainController;
use App\Http\Controllers\Tenants\Domains\CpanelController;
use App\Http\Controllers\Tenants\Domains\DomainController;
use App\Http\Controllers\Tenants\Domains\GetDomainInfoController;
use App\Http\Controllers\Tenants\Domains\RenewalDomainController;
use App\Http\Controllers\Tenants\GeneralSetting\Country\SettingsCountryController;
use App\Http\Controllers\Tenants\GeneralSetting\MessageTemplate\MessageTemplateController;
use App\Http\Controllers\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethodController;
use App\Http\Controllers\Tenants\License\LicenseController;
use App\Http\Controllers\Tenants\Notification\NotificationController;
use App\Http\Controllers\Tenants\Settings\ApiTokenSettingsController;
use App\Http\Controllers\Tenants\Settings\SettingsController;
use App\Http\Controllers\Tenants\Subscriptions\NewSubscriptionController;
use App\Http\Controllers\Tenants\Subscriptions\TenantSubscriptionController;
use App\Http\Controllers\Tenants\Tickets\TicketController;
use App\Http\Controllers\Tenants\UserMangment\Role\RoleController;
use App\Http\Controllers\Tenants\UserMangment\Team\TeamController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use App\Http\Controllers\Tenants\Versions\VersionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:tenant', 'tenant.2fa.force'])->group(function () {

    // لا يوجد اشتراك - صفحة الاشتراك والدفع
    Route::prefix('subscribe')->name('subscribe.')->group(function () {
        Route::name('index')->get('/', [NewSubscriptionController::class, 'index']);
        Route::name('checkout')->get('plan/{plan}/checkout', [NewSubscriptionController::class, 'checkout']);
        Route::name('checkout.store')->post('checkout', [NewSubscriptionController::class, 'checkoutStore']);
        Route::name('callback')->get('callback', [NewSubscriptionController::class, 'handleNewSubscriptionCallback']);
    });

    // الفواتير
    Route::prefix('bills')->name('bills.')->group(function () {
        Route::name('index')->get('/', [BillsController::class, 'index']);
        Route::name('callback')->get('callback', [BillsController::class, 'callback']);
        Route::name('show')->get('{id}', [BillsController::class, 'show']);
        Route::name('checkout')->get('{id}/checkout', [BillsController::class, 'checkout']);
        Route::name('checkout.process')->post('{id}/checkout', [BillsController::class, 'processCheckout']);

        // التحويل البنكي
        Route::name('bank-transfer')->get('{id}/bank-transfer', [BillsController::class, 'bankTransfer']);
        Route::name('upload-receipt')->post('{id}/upload-receipt', [BillsController::class, 'uploadReceipt']);
    });

    // عرض الاشتراك
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::name('index')->get('/', [TenantSubscriptionController::class, 'index']);
    });

    Route::middleware('tenant.license')->group(function () {
        // تجديد الاشتراك وشراء تراخيص
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::prefix('renew')->name('renew.')->group(function () {
                Route::get('/', [TenantSubscriptionController::class, 'renewCheckout'])->name('checkout');
                Route::post('/', [TenantSubscriptionController::class, 'processRenew'])->name('process');
                Route::get('/callback', [TenantSubscriptionController::class, 'renewCallback'])->name('callback');
            });

            Route::prefix('licenses')->name('licenses.')->group(function () {
                Route::get('/', [TenantSubscriptionController::class, 'licenseCheckout'])->name('checkout');
                Route::post('/', [TenantSubscriptionController::class, 'processLicense'])->name('process');
                Route::get('/callback', [TenantSubscriptionController::class, 'licenseCallback'])->name('callback');
            });
        });
    });

    // مسارات تشترط وجود اشتراك وترخيص نشط
    Route::middleware('tenant.subscription', 'tenant.license')->group(function () {

        // الدعم الفني والتذاكر
        Route::name('tickets.')->prefix('tickets')->group(function () {
            Route::resource('/', TicketController::class)->parameters(['' => 'ticket'])->only(['index', 'show', 'create', 'store']);
            Route::name('reply')->post('/reply/{ticket}', [TicketController::class, 'reply']);
            Route::name('replies.rate')->post('{ticket}/replies/{reply}/rate', [TicketController::class, 'rateReply']);
            Route::name('survey.submit')->post('{ticket}/survey', [TicketController::class, 'submitSurvey']);
        });

        // إعدادات النطاقات (Domains)
        Route::prefix('domains')->name('domains.')->group(function () {
            Route::name('setting')->get('setting', [DomainController::class, 'domainSetting']);
            Route::name('newdomain')->get('newdomain', [DomainController::class, 'newdomain']);
            Route::name('transfer-domain')->match(['post', 'get'], 'transfer-dom-blade', [DomainController::class, 'transferDom']);
            Route::name('link-domain')->get('link-domain', [DomainController::class, 'linkDomain']);
            Route::name('link-sub-domain')->get('link-sub-domain', [DomainController::class, 'linkSubDomain']);
            Route::name('faq')->get('faq', [DomainController::class, 'faq']);
            Route::name('explain')->get('explain', [DomainController::class, 'explain']);
            Route::name('subdomainblade')->get('subdomain', [DomainController::class, 'subdomainblade']);
            Route::name('get-domain-info')->get('getInfo/{domain}', [GetDomainInfoController::class, 'getDomainInfo']);

            // تجديد النطاق
            Route::name('payment.renewal')->match(['post', 'get'], 'renewal/payment', [RenewalDomainController::class, 'renewalDomainBlade']);
            Route::name('renewal')->post('renewal', [RenewalDomainController::class, 'renewalDomain']);
            Route::name('moyasar.renewal.callback')->get('renewal/payment/domain/callback', [MoyaserController::class, 'moyasarPaymentRenewalCallback']);
            Route::name('moyasar.renewal')->post('renewal/payment/domain', [MoyaserController::class, 'moyasarPaymentRenewal']);

            // التحقق من النطاقات
            Route::name('check')->get('/check/{domain}', [DomainsCheckMultipleDomainController::class, 'checkSingle'])->where('domain', '[a-zA-Z0-9.-]+');
            Route::post('/check-multiple', [DomainsCheckMultipleDomainController::class, 'checkMultiple'])->name('check-multiple');
            Route::get('/suggest-alternatives', [DomainsCheckMultipleDomainController::class, 'suggestAlternatives'])->name('suggest-alternatives');
            Route::get('/suggest-names', [DomainsCheckMultipleDomainController::class, 'suggestNames'])->name('suggest-names');
            Route::delete('/cache/{domain}', [DomainsCheckMultipleDomainController::class, 'clearCache'])->name('cache.clear')->where('domain', '[a-zA-Z0-9.-]+');
            Route::delete('/cache', [DomainsCheckMultipleDomainController::class, 'clearAllCache'])->name('cache.clear-all');

            // API Layer
            Route::name('apilayer')->match(['get', 'post'], 'check-domain/{domain}', [APILayerController::class, 'apiLayerFunc']);
            Route::name('check-domain')->match(['get', 'post'], 'check-domainss/{domain}', [APILayerController::class, 'apiLayer2']);
            Route::name('checkSubDomain')->get('checkSubdomain/{subdomain}', [APILayerController::class, 'checkSubDomain']);

            // Moyasar & Cpanel
            Route::name('payment.new')->match(['post', 'get'], 'payment', [MoyaserController::class, 'newDomain'])->middleware('check.domain.balance');
            Route::match(['get', 'post'], 'payment/proccess', [MoyaserController::class, 'newDomainPayment']);
            Route::name('payment.callbacknewdomain')->match(['get', 'post'], 'payment/callbacknewDomain', [MoyaserController::class, 'CallbacknewDomainPayment']);
            Route::get('createCpanelAccount/{username}', [DomainController::class, 'createCpanelAccount']);
            Route::name('process-transfer-payment')->post('process-transfer-payment', [MoyaserController::class, 'processTransferPayment']);
            Route::name('payment.callback.transfer')->match(['get', 'post'], 'process-transfer-payment-callback', [MoyaserController::class, 'processTransferCallback']);
            Route::name('checkExistTransferDomain')->post('checkExistTransferDomain/{domain}', [APILayerController::class, 'checkExistTransferDomain']);

            // Domain operations
            Route::name('transfer.domain.status')->match(['post', 'get'], 'checkTransferStatus/{domain}', [DomainController::class, 'checkTransferStatus']);
            Route::name('linkDomainproccess')->match(['post', 'get'], 'link-domain-proccess', [DomainController::class, 'setupdnsForDomain']);
            Route::post('/verify-dns', [DomainController::class, 'verifyDNS'])->name('domains.verify-dns');
            Route::name('check-domain-exists')->post('check-domain-exists', [DomainController::class, 'checkDomainExists']);
            Route::name('switch.active')->match(['post', 'get'], '/switch-active', [DomainController::class, 'switchActiveDomain']);
            Route::name('transfer-domain-proccess')->post('/transfer', [DomainController::class, 'transferDomainProcess']);
            Route::match(['post', 'get'], '/process-transfer', [DomainController::class, 'processTransfer']);
            Route::name('modifyaccount')->post('modifyaccount', [DomainController::class, 'modifyaccount']);
            Route::name('testverify')->get('verifydns/{domain}/{method}', [DomainController::class, 'verifyDNSTest']);

            // External domain link
            Route::name('link-external')->match(['post', 'get'], '/link-external-domain', [LinkDomainController::class, 'linkExternalDomain']);
            Route::name('link-external-process')->match(['post', 'get'], '/link-external-domain-process', [LinkDomainController::class, 'processLinkExternalDomain']);

            Route::name('check.balance')->match(['post', 'get'], '/check-balance', [DomainController::class, 'getBalance']);
            Route::name('get.info.domain')->match(['post', 'get'], '/get-domain-info/{domain}', [DomainController::class, 'getDomainInfo']);
            Route::name('get.result')->match(['post', 'get'], '/check-domain-result/{domain}', [DomainController::class, 'CalculateResults']);
            Route::name('cpanel.limits')->get('check-cpanel-limits', [CpanelController::class, 'checkLimits']);
        });

        // التراخيص (منح - إزالة)
        Route::prefix('licenses')->name('licenses.')->group(function () {
            Route::name('assign')->post('assign', [LicenseController::class, 'assign']);
            Route::name('release')->post('release', [LicenseController::class, 'release']);
        });

        // تحديث النسخة
        Route::prefix('versions')->name('versions.')->group(function () {
            Route::name('latest')->get('/', [VersionController::class, 'latest']);
            Route::name('upgrade')->post('/', [VersionController::class, 'upgrade']);
        });

        // التوجيه للرئيسية
        Route::get('/', function () {
            return redirect(tenant_route_name('dashboard'));
        });

        // لوحة القيادة
        Route::prefix('dashboard')->middleware(['verified'])->group(function () {
            Route::name('dashboard')->get('/', [DashboardController::class, 'index']);
        });

        // الفروع
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::name('index')->get('/', [BranchController::class, 'index']);
            Route::name('create')->get('create', [BranchController::class, 'create']);
            Route::name('store')->post('/', [BranchController::class, 'store']);
            Route::name('edit')->get('{branch}/edit', [BranchController::class, 'edit']);
            Route::name('update')->put('{branch}', [BranchController::class, 'update']);
            Route::name('destroy')->delete('{branch}', [BranchController::class, 'destroy']);
            Route::name('toggle-status')->patch('{branch}/toggle-status', [BranchController::class, 'toggleStatus']);
        });

        // الإشعارات
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/all', [NotificationController::class, 'allPage'])->name('all');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
            Route::post('/mass-delete', [NotificationController::class, 'massDelete'])->name('mass-delete');
        });

        // الإعدادات العامة (الدول - طرق الدفع - قوالب الرسائل)
        Route::prefix('general-settings')->name('general-settings.')->group(function () {
            Route::resource('settings-country', SettingsCountryController::class);
            Route::resource('settings-payment-methods', SettingsPaymentMethodController::class);

            Route::prefix('message-templates')->name('message-templates.')->group(function () {
                Route::name('preview')->post('/preview', [MessageTemplateController::class, 'preview']);
                Route::name('get-templates')->get('/get-templates', [MessageTemplateController::class, 'getTemplates']);
                Route::name('get-variables-by-category')->get('/get-variables-by-category', [MessageTemplateController::class, 'getVariablesByCategory']);
                Route::name('toggle-status')->patch('/{messageTemplate}/toggle-status', [MessageTemplateController::class, 'toggleStatus']);
            });
            Route::resource('message-templates', MessageTemplateController::class);
        });

        // إدارة المستخدمين
        Route::prefix('users')->name('users.')->group(function () {
            Route::name('index')->get('/', [UserController::class, 'index']);
            Route::name('create')->get('create', [UserController::class, 'create']);
            Route::name('profile')->get('profile', [UserController::class, 'showProfile']);
            Route::name('store')->post('/', [UserController::class, 'store']);
            Route::name('show')->get('{user}', [UserController::class, 'show']);
            Route::name('edit')->get('{user}/edit', [UserController::class, 'edit']);
            Route::name('update')->put('{user}', [UserController::class, 'update']);
            Route::name('update-image')->post('{user}/image', [UserController::class, 'updateImage']);
            Route::name('update-background')->post('{user}/background', [UserController::class, 'updateBackgroundImage']);
            Route::name('destroy')->delete('{user}', [UserController::class, 'destroy']);
            Route::name('toggle-status')->patch('{user}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::name('password.change')->get('password/change', [UserController::class, 'showChangePasswordForm']);
            Route::name('password.update')->post('password/change', [UserController::class, 'updatePassword']);
        });

        // إدارة فرق العمل
        Route::prefix('teams')->name('teams.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::get('create', [TeamController::class, 'create'])->name('create');
            Route::post('/', [TeamController::class, 'store'])->name('store');
            Route::get('{team}', [TeamController::class, 'show'])->name('show');
            Route::get('{team}/edit', [TeamController::class, 'edit'])->name('edit');
            Route::put('{team}', [TeamController::class, 'update'])->name('update');
            Route::delete('{team}', [TeamController::class, 'destroy'])->name('destroy');
            Route::patch('{team}/toggle-status', [TeamController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('users-by-branch/{branchId}', [TeamController::class, 'getUsersByBranch'])->name('users-by-branch');
        });

        // الأدوار والصلاحيات
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::name('index')->get('/', [RoleController::class, 'index']);
            Route::name('create')->get('create', [RoleController::class, 'create']);
            Route::name('store')->post('/', [RoleController::class, 'store']);
            Route::name('user.get')->get('users/{id}/get-roles', [RoleController::class, 'getUserRoles']);
            Route::name('user.update')->post('users/{id}/update-roles', [RoleController::class, 'updateUserRoles']);
            Route::name('user.status')->patch('users/{userId}/status', [RoleController::class, 'updateStatus']);
            Route::name('show')->get('{role}', [RoleController::class, 'show']);
            Route::name('edit')->get('{role}/edit', [RoleController::class, 'edit']);
            Route::name('update')->put('{role}', [RoleController::class, 'update']);
            Route::name('destroy')->delete('{role}', [RoleController::class, 'destroy']);
        });

        // سجل الرسائل
        Route::prefix('communication')->name('communication.')->group(function () {
            Route::prefix('message-logs')->name('message-logs.')->group(function () {
                Route::name('index')->get('/', [MessageLogController::class, 'index']);
                Route::name('show')->get('{messageLog}', [MessageLogController::class, 'show']);
                Route::name('destroy')->delete('{messageLog}', [MessageLogController::class, 'destroy']);
                Route::name('dashboard-stats')->get('dashboard/stats', [MessageLogController::class, 'dashboardStats']);
                Route::name('chart-data')->get('chart/data', [MessageLogController::class, 'chartData']);
                Route::name('failed-messages')->get('failed/messages', [MessageLogController::class, 'failedMessages']);
            });

            Route::prefix('message-individual-logs')->name('message-individual-logs.')->group(function () {
                Route::name('index')->get('/', [MessageIndividualLogController::class, 'index']);
                Route::name('archives')->get('archives', [MessageIndividualLogController::class, 'archives']);
                Route::name('show')->get('{messageIndividualLog}', [MessageIndividualLogController::class, 'show']);
                Route::name('destroy')->delete('{messageIndividualLog}', [MessageIndividualLogController::class, 'destroy']);
                Route::name('dashboard-stats')->get('dashboard/stats', [MessageIndividualLogController::class, 'dashboardStats']);
                Route::name('chart-data')->get('chart/data', [MessageIndividualLogController::class, 'chartData']);
                Route::name('export')->post('export', [MessageIndividualLogController::class, 'export']);
                Route::name('download')->get('download/{file}', [MessageIndividualLogController::class, 'downloadFile']);
            });
        });

        // إعدادات الشركة
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::name('index')->get('/', [SettingsController::class, 'index']);
            Route::name('update')->put('/', [SettingsController::class, 'update']);
            Route::name('center')->get('/center', [SettingsController::class, 'center']);
        });

        // توكن الـ API
        Route::prefix('api-token-settings')->name('api-token-settings.')->group(function () {
            Route::name('index')->get('/', [ApiTokenSettingsController::class, 'index']);
            Route::name('regenerate')->post('/regenerate', [ApiTokenSettingsController::class, 'regenerate']);
        });

        // سجل النشاطات
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::name('index')->get('/', [ActivityLogController::class, 'index']);
            Route::name('archives')->get('archives', [ActivityLogController::class, 'archives']);
            Route::name('get-all-ids')->get('get-all-ids', [ActivityLogController::class, 'getAllIds']);
            Route::name('export')->post('export', [ActivityLogController::class, 'export']);
            Route::name('export-download')->get('export-download/{file}', [ActivityLogController::class, 'downloadExportFile']);
            Route::name('show')->get('{id}', [ActivityLogController::class, 'show']);
        });

        // أرشيف المستخدمين
        Route::prefix('archive')->name('archive.')->group(function () {
            Route::prefix('users')->name('users.')->group(function () {
                Route::name('index')->get('/', [UserArchiveController::class, 'index']);
                Route::name('restore')->patch('{user}/restore', [UserArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{user}/force-delete', [UserArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [UserArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [UserArchiveController::class, 'massForceDelete']);
            });
        });

        // الحذف الجماعي
        Route::name('mass-delete')->post('/mass-delete', [MassDeleteController::class, 'massDelete']);
    });
});

/*
|--------------------------------------------------------------------------
| Authentication: Password Reset
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->prefix('forgot-password')->group(function () {
    Route::name('password.request')->get('/', [PasswordResetLinkController::class, 'create']);
    Route::name('password.email')->post('/', [PasswordResetLinkController::class, 'store']);
});

require __DIR__ . '/tenant-auth.php';
