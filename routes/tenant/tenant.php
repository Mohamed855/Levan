<?php

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\__LinkDomain\LinkDomainController;
use App\Http\Controllers\Tenants\activity_log\ActivityLogController;
use App\Http\Controllers\Tenants\Archive\Assignment\AssignmentArchiveController;
use App\Http\Controllers\Tenants\Archive\Auction\AuctionArchiveController;
use App\Http\Controllers\Tenants\Archive\Company\CompanyArchiveController;
use App\Http\Controllers\Tenants\Archive\Contract\ContractArchiveController;
use App\Http\Controllers\Tenants\Archive\Customer\CustomerArchiveController;
use App\Http\Controllers\Tenants\Archive\Portfolio\PortfolioArchiveController;
use App\Http\Controllers\Tenants\Archive\Property\PropertyArchiveController;
use App\Http\Controllers\Tenants\Archive\User\UserArchiveController;
use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorSettingsController;
use App\Http\Controllers\Tenants\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenants\Bills\BillsController;
use App\Http\Controllers\Tenants\BusinessCenter\Company\CompanyController;
use App\Http\Controllers\Tenants\BusinessCenter\Contract\ContractController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Attachment\CustomerAttachmentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Comment\CustomerCommentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Distribution\CustomerDistributionController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Export\CustomerExportController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Follow\CustomerFollowController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Import\CustomerImportController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Import\GetCustomerImportController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Letter\CompanyLetterController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Note\CustomerNoteController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Payment\CustomerPaymentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\PaymentHistory\CustomerPaymentHistoryController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\PaymentPlan\CustomerPaymentPlanController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\PhoneRequest\CustomerPhoneRequestController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Settlement\CustomerSettlementController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Sms\CustomerSmsController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\UpdateRequest\CustomerUpdateRequestController;
use App\Http\Controllers\Tenants\BusinessCenter\Portfolio\PortfolioController;
use App\Http\Controllers\Tenants\BusinessCenter\Task\MyTaskController;
use App\Http\Controllers\Tenants\BusinessCenter\Task\TaskController;
use App\Http\Controllers\Tenants\ChartSetting\ChartSettingController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageIndividualLogController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageLogController;
use App\Http\Controllers\Tenants\Dashboard\DashboardController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use App\Http\Controllers\Tenants\Domains\CheckMultipleDomainController as DomainsCheckMultipleDomainController;
use App\Http\Controllers\Tenants\Domains\CpanelController;
use App\Http\Controllers\Tenants\Domains\DomainController;
use App\Http\Controllers\Tenants\Domains\DomainLookupController;
use App\Http\Controllers\Tenants\Domains\DomainPaymentController;
use App\Http\Controllers\Tenants\Domains\GetDomainInfoController;
use App\Http\Controllers\Tenants\Domains\RenewalDomainController;
use App\Http\Controllers\Tenants\FinancialCollection\Admin\AdminCollectionsController;
use App\Http\Controllers\Tenants\FinancialCollection\Collection\CollectionController;
use App\Http\Controllers\Tenants\FinancialCollection\Import\CollectionImportController;
use App\Http\Controllers\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationController;
use App\Http\Controllers\Tenants\FinancialCollection\Target\CollectionTargetController;
use App\Http\Controllers\Tenants\GeneralSetting\ContactMethod\SettingsContactMethodController;
use App\Http\Controllers\Tenants\GeneralSetting\Country\SettingsCountryController;
use App\Http\Controllers\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatusController;
use App\Http\Controllers\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReasonController;
use App\Http\Controllers\Tenants\GeneralSetting\MessageTemplate\MessageTemplateController;
use App\Http\Controllers\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethodController;
use App\Http\Controllers\Tenants\License\LicenseController;
use App\Http\Controllers\Tenants\Notification\NotificationController;
use App\Http\Controllers\Tenants\Properties\PropertiesController;
use App\Http\Controllers\Tenants\Reports\Company\CompanyReportController;
use App\Http\Controllers\Tenants\Reports\Customer\CustomerReportController;
use App\Http\Controllers\Tenants\Reports\CustomerFollow\CustomerFollowReportController;
use App\Http\Controllers\Tenants\Reports\Executive\ExecutiveReportController;
use App\Http\Controllers\Tenants\Reports\Financial\FinancialReportController;
use App\Http\Controllers\Tenants\Reports\Performance\EmployeePerformanceReportController;
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
|============================================================================
|============================================================================
|                               Dashboard
|============================================================================
|============================================================================
*/

Route::middleware(['auth:tenant', 'tenant.2fa.force'])->group(function () {
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function () {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
        Route::delete('file', [WizardDraftController::class, 'destroyFile'])->name('file.destroy');
        Route::post('validate-step', [WizardDraftController::class, 'validateStep'])
            ->defaults('shared_form_validation_actions', [
                UserController::class.'@store',
                UserController::class.'@update',
                UserController::class.'@updateImage',
                UserController::class.'@updateBackgroundImage',
                UserController::class.'@updatePassword',
                TeamController::class.'@store',
                TeamController::class.'@update',
                RoleController::class.'@store',
                RoleController::class.'@update',
                SettingsController::class.'@update',
                CompanyController::class.'@store',
                CompanyController::class.'@update',
                ContractController::class.'@store',
                ContractController::class.'@update',
                PortfolioController::class.'@store',
                PortfolioController::class.'@update',
                AssignmentsController::class.'@store',
                AssignmentsController::class.'@update',
                PropertiesController::class.'@store',
                PropertiesController::class.'@update',
                AuctionsController::class.'@store',
                AuctionsController::class.'@update',
                CustomerController::class.'@store',
                CustomerController::class.'@update',
                CollectionController::class.'@store',
                CollectionController::class.'@update',
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
                SettingsCountryController::class.'@store',
                SettingsCountryController::class.'@update',
                SettingsPaymentMethodController::class.'@store',
                SettingsPaymentMethodController::class.'@update',
                SettingsContactMethodController::class.'@store',
                SettingsContactMethodController::class.'@update',
                SettingsDocumentRequestReasonController::class.'@store',
                SettingsDocumentRequestReasonController::class.'@update',
                TenantTwoFactorSettingsController::class.'@confirm',
            ])
            ->name('validate-step');
    });

    // لا يوجد اشتراك
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

        // الاشتراكات
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

    // لا بد من وجود اشتراك نشط

    Route::middleware('tenant.subscription', 'tenant.license')->group(function () {

        // الدعم الفني
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

        // اعدادات الدومين
        Route::prefix('domains')->name('domains.')->group(function () {

            // start blade for domains
            Route::name('setting')->get('setting', [DomainController::class, 'domainSetting']);
            Route::name('newdomain')->get('newdomain', [DomainController::class, 'newdomain']);
            Route::name('transfer-domain')->get('transfer-dom-blade', [DomainController::class, 'transferDom']);
            Route::name('link-domain')->get('link-domain', [DomainController::class, 'linkDomain']);
            Route::name('link-sub-domain')->get('link-sub-domain', [DomainController::class, 'linkSubDomain']);
            Route::name('faq')->get('faq', [DomainController::class, 'faq']);
            Route::name('explain')->get('explain', [DomainController::class, 'explain']);
            Route::name('subdomainblade')->get('subdomain', [DomainController::class, 'subdomainblade']);
            // end blade for domains

            // start get domain info
            Route::name('get-domain-info')->get('getInfo/{domain}', [GetDomainInfoController::class, 'getDomainInfo']);
            // end get domain info

            // start domain renewal
            Route::name('payment.renewal')->match(['post', 'get'], 'renewal/payment', [RenewalDomainController::class, 'renewalDomainBlade']);
            Route::name('renewal')->post('renewal', [RenewalDomainController::class, 'renewalDomain']);
            // end domain renewal

            // moyasar for domain renewal
            Route::name('moyasar.renewal.callback')->get('renewal/payment/domain/callback', [DomainPaymentController::class, 'renewalCallback']);
            Route::name('moyasar.renewal')->post('renewal/payment/domain', [DomainPaymentController::class, 'startRenewal']);
            // moyasar for domain renewal

            // start check multiple domain
            Route::name('check')->get('/check/{domain}', [DomainsCheckMultipleDomainController::class, 'checkSingle'])

                ->where('domain', '[a-zA-Z0-9.-]+');

            // التحقق من عدة دومينات
            Route::post('/check-multiple', [DomainsCheckMultipleDomainController::class, 'checkMultiple'])
                ->name('check-multiple');

            // اقتراح دومينات بديلة بامتدادات مختلفة
            Route::get('/suggest-alternatives', [DomainsCheckMultipleDomainController::class, 'suggestAlternatives'])
                ->name('suggest-alternatives');

            // اقتراح دومينات بديلة بتعديلات على الاسم
            Route::get('/suggest-names', [DomainsCheckMultipleDomainController::class, 'suggestNames'])
                ->name('suggest-names');

            // مسح الـ Cache
            Route::delete('/cache/{domain}', [DomainsCheckMultipleDomainController::class, 'clearCache'])
                ->name('cache.clear')
                ->where('domain', '[a-zA-Z0-9.-]+');

            Route::delete('/cache', [DomainsCheckMultipleDomainController::class, 'clearAllCache'])
                ->name('cache.clear-all');

            // end check multiple domain

            // start api layer
            Route::name('apilayer')->match(['get', 'post'], 'check-domain/{domain}', [DomainLookupController::class, 'checkAvailability']);
            Route::name('check-domain')->match(['get', 'post'], 'check-domainss/{domain}', [DomainLookupController::class, 'inspectDns']);
            Route::name('checkSubDomain')->get('checkSubdomain/{subdomain}', [DomainLookupController::class, 'checkSubDomain']);
            // end api layer

            // moyaser start
            Route::name('payment.new')->post('payment', [DomainPaymentController::class, 'startRegistration'])->middleware('check.domain.balance');
            Route::name('payment.callbacknewdomain')->get('payment/callbacknewDomain', [DomainPaymentController::class, 'registrationCallback']);
            Route::name('process-transfer-payment')->post('process-transfer-payment', [DomainPaymentController::class, 'startTransfer']);
            Route::name('payment.callback.transfer')->get('process-transfer-payment-callback', [DomainPaymentController::class, 'transferCallback']);
            // moyaser end

            Route::name('checkExistTransferDomain')->post('checkExistTransferDomain/{domain}', [DomainLookupController::class, 'checkTransferAvailability']);

            // start domain controller
            Route::name('transfer.domain.status')->get('checkTransferStatus/{domain}', [DomainController::class, 'checkTransferStatus']);
            Route::name('linkDomainproccess')->post('link-domain-proccess', [DomainController::class, 'setupdnsForDomain']);
            Route::post('/verify-dns', [DomainController::class, 'verifyDNS'])->name('domains.verify-dns');
            Route::name('check-domain-exists')->post('check-domain-exists', [DomainController::class, 'checkDomainExists']);
            Route::name('switch.active')->post('/switch-active', [DomainController::class, 'switchActiveDomain']);
            Route::name('transfer-domain-proccess')->post('/transfer', [DomainController::class, 'transferDomainProcess']);
            Route::post('/process-transfer', [DomainController::class, 'processTransfer']);
            Route::name('modifyaccount')->post('modifyaccount', [DomainController::class, 'modifyaccount']);
            // end domain controller
            Route::name('testverify')->get('verifydns/{domain}/{method}', [DomainController::class, 'verifyDNSTest']);
            // start link domain controller

            Route::name('link-external')->get('/link-external-domain', [LinkDomainController::class, 'linkExternalDomain']);
            // معالجة ربط دومين خارجي
            Route::name('link-external-process')->post('/link-external-domain-process', [LinkDomainController::class, 'processLinkExternalDomain']);

            // balance and domain pricing
            Route::name('check.balance')->get('/check-balance', [DomainController::class, 'getBalance']);
            Route::name('get.info.domain')->get('/get-domain-info/{domain}', [GetDomainInfoController::class, 'getDomainInfo']);
            Route::name('get.result')->post('/check-domain-result/{domain}', [DomainController::class, 'calculateResults']);

            // check cpanel limits
            Route::name('cpanel.limits')->get('check-cpanel-limits', [CpanelController::class, 'checkLimits']);
        });

        // التراخيص  منح - ازالة
        Route::prefix('licenses')->name('licenses.')->group(function () {
            Route::name('assign')->post('assign', [LicenseController::class, 'assign']);
            Route::name('release')->post('release', [LicenseController::class, 'release']);
        });

        // تحديث النسخة
        Route::prefix('versions')->name('versions.')->group(function () {
            Route::name('latest')->get('/', [VersionController::class, 'latest']);
            Route::name('upgrade')->post('/', [VersionController::class, 'upgrade']);
        });

        Route::get('/', function () {
            return redirect(tenant_route_name('dashboard'));
        });

        Route::prefix('dashboard')->middleware(['verified'])->group(function () {
            Route::name('dashboard')->get('/', [DashboardController::class, 'index']);
            Route::name('dashboard.upcomingFollowUps')->get('/upcoming-follow-ups', [DashboardController::class, 'getUpcomingFollowUps']);
            Route::name('dashboard.myUpcomingFollowUps')->get('/my-upcoming-followups', [DashboardController::class, 'getMyUpcomingFollowUps']);
            Route::name('dashboard.exportSessions')->get('/export-sessions', [DashboardController::class, 'exportCollectionSessions']);
            Route::name('dashboard.excellence')->get('/excellence', [DashboardController::class, 'excellence']);
            Route::name('dashboard.myPerformanceComparison')->get('/my-performance-comparison', [DashboardController::class, 'getMyPerformanceComparison']);
            Route::name('dashboard.statistics')->get('/statistics', [DashboardController::class, 'getStatisticsData']);
            Route::name('dashboard.approvals')->get('/approvals', [DashboardController::class, 'getApprovalsData']);
            Route::name('dashboard.overdueTasks')->get('/overdue-tasks', [DashboardController::class, 'getOverdueTasks']);
            Route::name('dashboard.exportOverdueTasks')->get('/export-overdue-tasks', [DashboardController::class, 'exportOverdueTasks']);
            Route::name('dashboard.myOverdueTasks')->get('/my-overdue-tasks', [DashboardController::class, 'getMyOverdueTasks']);
            Route::name('dashboard.exportMyOverdueTasks')->get('/export-my-overdue-tasks', [DashboardController::class, 'exportMyOverdueTasks']);
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

        Route::prefix('general-settings')->name('general-settings.')->group(function () {
            Route::resource('settings-country', SettingsCountryController::class);
            Route::resource('settings-payment-methods', SettingsPaymentMethodController::class);
            Route::resource('settings-contact-methods', SettingsContactMethodController::class);

            Route::patch('settings-country/{id}/toggle-status', [SettingsCountryController::class, 'edit'])->name('settings-country.toggle-status');
            Route::patch('settings-payment-methods/{id}/toggle-status', [SettingsPaymentMethodController::class, 'edit'])->name('settings-payment-methods.toggle-status');
            Route::patch('settings-contact-methods/{id}/toggle-status', [SettingsContactMethodController::class, 'edit'])->name('settings-contact-methods.toggle-status');

            // === راوتات أسباب طلب المستندات ===
            Route::resource('settings-document-request-reasons', SettingsDocumentRequestReasonController::class);
            Route::patch('settings-document-request-reasons/{id}/toggle-status', [SettingsDocumentRequestReasonController::class, 'edit'])->name('settings-document-request-reasons.toggle-status');

            //  === راوتات حالات العملاء ===
            Route::resource('settings-customer-status', SettingsCustomerStatusController::class);
            Route::prefix('settings-customer-status')->name('settings-customer-status.')->group(function () {
                Route::name('toggleStatus')->patch('/{id}/toggle-status', [SettingsCustomerStatusController::class, 'toggleStatus']);
            });

            //  === راوتات قوالب الرسائل  ===
            Route::prefix('message-templates')->name('message-templates.')->group(function () {
                Route::name('preview')->post('/preview', [MessageTemplateController::class, 'preview']);
                Route::name('get-templates')->get('/get-templates', [MessageTemplateController::class, 'getTemplates']);
                Route::name('get-variables-by-category')->get('/get-variables-by-category', [MessageTemplateController::class, 'getVariablesByCategory']);
                Route::name('toggle-status')->patch('/{messageTemplate}/toggle-status', [MessageTemplateController::class, 'toggleStatus']);
            });
            Route::resource('message-templates', MessageTemplateController::class);

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
        | Team Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('teams')->name('teams.')->group(function () {
            Route::patch('{team}/toggle-status', [TeamController::class, 'toggleStatus'])->name('toggle-status');
            Route::resource('/', TeamController::class)->parameters(['' => 'team']);
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
        | Assignments Management
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
        | Properties Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::name('toggle-status')->patch('{property}/toggle-status', [PropertiesController::class, 'toggleStatus']);
        });
        Route::resource('properties', PropertiesController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Auctions Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('auctions')->name('auctions.')->group(function () {
            Route::name('toggle-status')->patch('{auction}/toggle-status', [AuctionsController::class, 'toggleStatus']);
        });
        Route::resource('auctions', AuctionsController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Company Management
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
        | Contract Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::name('index')->get('/', [ContractController::class, 'index']);
            Route::name('create')->get('create', [ContractController::class, 'create']);
            Route::name('store')->post('/', [ContractController::class, 'store']);
            Route::name('show')->get('{contract}', [ContractController::class, 'show']);
            Route::name('edit')->get('{contract}/edit', [ContractController::class, 'edit']);
            Route::name('update')->put('{contract}', [ContractController::class, 'update']);
            Route::name('destroy')->delete('{contract}', [ContractController::class, 'destroy']);
            Route::name('toggle-status')->patch('{contract}/toggle-status', [ContractController::class, 'toggleStatus']);
            Route::name('generate-number')->get('generate-number', [ContractController::class, 'generateContractNumber']);
            Route::name('check-number')->post('check-number', [ContractController::class, 'checkContractNumber']);
        });

        /*
        |--------------------------------------------------------------------------
        | Portfolio Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('portfolios')->name('portfolios.')->group(function () {
            Route::name('index')->get('/', [PortfolioController::class, 'index']);
            Route::name('create')->get('create', [PortfolioController::class, 'create']);
            Route::name('store')->post('/', [PortfolioController::class, 'store']);
            Route::name('edit')->get('{portfolio}/edit', [PortfolioController::class, 'edit']);
            Route::name('update')->put('{portfolio}', [PortfolioController::class, 'update']);
            Route::name('destroy')->delete('{portfolio}', [PortfolioController::class, 'destroy']);
            Route::name('toggle-status')->patch('{portfolio}/toggle-status', [PortfolioController::class, 'toggleStatus']);
            Route::name('recent-assignments')->get('recent-assignments', [PortfolioController::class, 'getRecentAssignments']);
            Route::name('show')->get('{portfolio}', [PortfolioController::class, 'show']);
        });

        /*
        |--------------------------------------------------------------------------
        | Tasks Management (للإدارة)
        |--------------------------------------------------------------------------
        */
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::name('index')->get('/', [TaskController::class, 'index']);
            Route::get('export', [TaskController::class, 'export'])->name('export');
        });

        /*
        |--------------------------------------------------------------------------
        | My Tasks Management (للموظف)
        |--------------------------------------------------------------------------
        */
        Route::prefix('my-tasks')->name('my-tasks.')->group(function () {
            Route::name('index')->get('/', [MyTaskController::class, 'index']);
            Route::get('export', [MyTaskController::class, 'export'])->name('export');
        });

        /*
        |--------------------------------------------------------------------------
        | Customer Management
        |--------------------------------------------------------------------------
        */
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::name('index')->get('/', [CustomerController::class, 'index']);
            Route::name('my-customers')->get('my-customers', [CustomerController::class, 'index']);
            Route::name('get-all-ids')->get('get-all-ids', [CustomerController::class, 'getAllCustomerIds']);
            Route::name('create')->get('create', [CustomerController::class, 'create']);
            Route::name('store')->post('/', [CustomerController::class, 'store']);
            Route::name('edit')->get('{customer}/edit', [CustomerController::class, 'edit']);
            Route::name('update')->put('{customer}', [CustomerController::class, 'update']);
            Route::name('destroy')->delete('{customer}', [CustomerController::class, 'destroy']);
            Route::name('set-primary-phone')->post('{customer}/set-primary-phone', [CustomerController::class, 'setPrimaryPhone']);
            Route::name('available-statuses')->get('available-statuses', [CustomerController::class, 'getAvailableStatuses']);
            Route::name('fields-by-portfolio')->get('fields-by-portfolio', [CustomerController::class, 'getPortfolioFields']);
            Route::name('get-phone-number')->get('{customer}/get-phone-number', [CustomerController::class, 'getCustomerPhoneNumber']);
            Route::name('whatsapp-preview-template')->get('whatsapp-preview-template', [CustomerController::class, 'previewWhatsAppTemplate']);

            Route::name('portfolios-by-company')->get('portfolios-by-company', [CustomerController::class, 'getPortfoliosByCompany']);

            // === مسارات التحديث المجتمع العملاء ===
            Route::prefix('bulk-update')->name('bulk-update.')->group(function () {
                Route::name('index')->get('/', [CustomerBulkUpdateController::class, 'index']);
                Route::name('get-portfolios')->get('/get-portfolios', [CustomerBulkUpdateController::class, 'getPortfolios']);
                Route::name('get-updatable-fields')->get('/get-updatable-fields', [CustomerBulkUpdateController::class, 'getUpdatableFields']);
                Route::name('analyze-headers')->post('/analyze-headers', [CustomerBulkUpdateController::class, 'analyzeHeaders']);
                Route::name('store-temp-file')->post('/store-temp-file', [CustomerBulkUpdateController::class, 'storeTemporaryFile']);
                Route::name('process')->post('/process', [CustomerBulkUpdateController::class, 'process']);
                Route::name('get-progress')->get('/get-progress', [CustomerBulkUpdateController::class, 'getProgress']);
                Route::name('download-template')->post('/download-template', [CustomerBulkUpdateController::class, 'downloadTemplate']);
                Route::name('download-error-report')->get('/download-error-report', [CustomerBulkUpdateController::class, 'downloadErrorReport']);
                Route::name('process-exclusion')->post('/process-exclusion', [CustomerBulkUpdateController::class, 'processExclusion']);
                Route::name('download-exclusion-template')->post('/download-exclusion-template', [CustomerBulkUpdateController::class, 'downloadExclusionTemplate']);
                Route::name('get-exclusion-progress')->get('/get-exclusion-progress', [CustomerBulkUpdateController::class, 'getProgress']);
            });

            // === مسارات توزيع العملاء ===
            Route::prefix('distribution')->name('distribution.')->group(function () {
                Route::name('index')->get('/', [CustomerDistributionController::class, 'index']);
                Route::name('portfolios-with-unassigned-customers')->get('portfolios-with-unassigned-customers', [CustomerDistributionController::class, 'getPortfoliosWithUnassignedCustomers']);
                Route::name('show')->get('{id}/show', [CustomerDistributionController::class, 'show']);
                Route::name('create')->get('create', [CustomerDistributionController::class, 'create']);
                Route::name('preview')->post('preview', [CustomerDistributionController::class, 'preview']);
                Route::name('calculate-targets')->post('calculate-targets', [CustomerDistributionController::class, 'calculateTargets']);
                Route::name('execute')->post('execute', [CustomerDistributionController::class, 'execute']);

                Route::name('collectors-by-team')->get('/collectors-by-team', [CustomerDistributionController::class, 'getCollectorsByTeam']);
            });

            // === مسارات استيراد العملاء ===
            Route::prefix('import')->name('import.')->group(function () {
                Route::name('index')->get('/', [CustomerImportController::class, 'index']);
                Route::name('portfolios-by-company')->get('/portfolios-by-company', [GetCustomerImportController::class, 'getPortfoliosByCompany']);
                Route::name('download-template')->get('/download-template', [CustomerImportController::class, 'downloadTemplate']);
                Route::name('preview-template')->get('/preview-template', [CustomerImportController::class, 'previewTemplate']);
                Route::name('upload-analyze')->post('/upload-analyze', [CustomerImportController::class, 'uploadAndAnalyze']);

                Route::name('process')->post('/process', [CustomerImportController::class, 'processImport']);
                Route::name('download-error-report')->get('/download-error-report', [CustomerImportController::class, 'downloadErrorReport']);
                Route::name('cancel')->post('/cancel', [CustomerImportController::class, 'cancelImport']);
                Route::name('check-status')->get('/check-status', [CustomerImportController::class, 'checkImportStatus']);
                Route::name('cancel-queued')->post('/cancel-queued', [CustomerImportController::class, 'cancelQueuedImport']);
                Route::name('queue-status')->get('/queue-status', [CustomerImportController::class, 'getQueueStatus']);

                // لجلب البيانات
                Route::get('/metadata', [GetCustomerImportController::class, 'getImportMetadata'])->name('metadata');
            });
            // === مسارات استيراد العملاء ===

            // == طلبات تحديث بيانات العملاء
            Route::prefix('update-requests')->name('update-requests.')->group(function () {
                Route::name('index')->get('/', [CustomerUpdateRequestController::class, 'index']);
                Route::name('pending-count')->get('pending-count', [CustomerUpdateRequestController::class, 'getPendingCount']);
                Route::name('show')->get('{customerUpdateRequest}', [CustomerUpdateRequestController::class, 'show']);
                Route::name('approve')->post('{customerUpdateRequest}/approve', [CustomerUpdateRequestController::class, 'approve']);
                Route::name('reject')->post('{customerUpdateRequest}/reject', [CustomerUpdateRequestController::class, 'reject']);
                Route::name('bulk-approve')->post('bulk-approve', [CustomerUpdateRequestController::class, 'bulkApprove']);
                Route::name('bulk-reject')->post('bulk-reject', [CustomerUpdateRequestController::class, 'bulkReject']);
                Route::name('export-fields')->post('export-fields', [CustomerUpdateRequestController::class, 'exportFields']);
                Route::name('export-excel')->post('export-excel', [CustomerUpdateRequestController::class, 'exportExcel']);
            });

            // == في مجموعة مسارات العملاء ==
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/phone-requests')->name('phone-requests.')->group(function () {
                Route::name('list')->get('list', [CustomerPhoneRequestController::class, 'getPhoneRequestsList']);
                Route::name('create')->get('create', [CustomerPhoneRequestController::class, 'create']);
                Route::name('store')->post('/', [CustomerPhoneRequestController::class, 'store']);
            });
            // == في مجموعة مسارات العملاء ==

            Route::name('show')->get('{customer}', [CustomerController::class, 'show']);

            // === مسارات تحصيلات العملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/payments')->name('payments.')->group(function () {
                Route::name('create')->get('create', [CustomerPaymentController::class, 'create']);
                Route::name('store')->post('/', [CustomerPaymentController::class, 'store']);
                Route::name('edit')->get('{collection}/edit', [CustomerPaymentController::class, 'edit']);
                Route::name('update')->put('{collection}', [CustomerPaymentController::class, 'update']);
                Route::name('destroy')->delete('{collection}', [CustomerPaymentController::class, 'destroy']);
                Route::name('generate-receipt')->get('generate-receipt', [CustomerPaymentController::class, 'generateReceiptNumber']);
                Route::name('statistics')->get('statistics', [CustomerPaymentController::class, 'statistics']);
                Route::name('recent')->get('recent', [CustomerPaymentController::class, 'recentPayments']);
                Route::name('datatable')->get('datatable', [CustomerPaymentController::class, 'dataTable']);
            });

            // === المسارات الجديدة للرسائل النصية ===
            Route::prefix('sms')->name('sms.')->group(function () {
                Route::name('form')->post('form', [CustomerController::class, 'showSMSForm']);
                Route::name('send')->post('send', [CustomerController::class, 'sendSMS']);
                Route::name('preview')->post('preview', [CustomerController::class, 'previewTemplateMessage']);
            });

            // === مسارات متابعة العملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/follows')->name('follows.')->group(function () {
                Route::name('index')->get('/', [CustomerFollowController::class, 'index']);
                Route::name('list')->get('list', [CustomerFollowController::class, 'getFollowsList']);
                Route::name('contact-result-fields')->get('contact-result-fields', [CustomerFollowController::class, 'getContactResultFields']);
                Route::name('next-customer')->get('next-customer', [CustomerFollowController::class, 'getNextCustomer']);
                Route::name('get-customer-debt')->get('get-customer-debt', [CustomerFollowController::class, 'getCustomerDebt']);
                Route::name('get-related-accounts')->get('get-related-accounts', [CustomerFollowController::class, 'getRelatedAccounts']);
                Route::name('create')->get('create', [CustomerFollowController::class, 'create']);
                Route::name('store')->post('/', [CustomerFollowController::class, 'store']);
                Route::name('show')->get('{customerFollow}', [CustomerFollowController::class, 'show']);
                Route::name('edit')->get('{customerFollow}/edit', [CustomerFollowController::class, 'edit']);
                Route::name('update')->put('{customerFollow}', [CustomerFollowController::class, 'update']);
                Route::name('destroy')->delete('{customerFollow}', [CustomerFollowController::class, 'destroy']);
            });

            // === مسارات مرفقات العملاء المبسطة ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/attachments')->name('attachments.')->group(function () {
                Route::name('list')->get('list', [CustomerAttachmentController::class, 'getAttachmentsList']);
                Route::name('store')->post('/', [CustomerAttachmentController::class, 'store']);
                Route::name('destroy')->delete('{customerAttachment}', [CustomerAttachmentController::class, 'destroy']);
                Route::name('download')->get('{customerAttachment}/download', [CustomerAttachmentController::class, 'download']);
            });

            // === مسارات ملاحظات العملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/notes')->name('notes.')->group(function () {
                Route::name('list')->get('list', [CustomerNoteController::class, 'getNotesList']);
                Route::name('store')->post('/', [CustomerNoteController::class, 'store']);
                Route::name('destroy')->delete('{customerNote}', [CustomerNoteController::class, 'destroy']);
            });

            // === مسارات تعليقات العملاء (الجديدة) ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/comments')->name('comments.')->group(function () {
                Route::name('list')->get('list', [CustomerCommentController::class, 'getCommentsList']);
                Route::name('store')->post('/', [CustomerCommentController::class, 'store']);
                Route::name('destroy')->delete('{customerComment}', [CustomerCommentController::class, 'destroy']);
                Route::name('mentionable-users')->get('mentionable-users', [CustomerCommentController::class, 'getMentionableUsers']);
            });

            // === مسارات خطط سداد العملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/payment-plans')->name('payment-plans.')->group(function () {
                Route::name('list')->get('list', [CustomerPaymentPlanController::class, 'getPaymentPlansList']);
                Route::name('create')->get('create', [CustomerPaymentPlanController::class, 'create']);
                Route::name('store')->post('/', [CustomerPaymentPlanController::class, 'store']);
                Route::name('edit')->get('{customerPaymentPlan}/edit', [CustomerPaymentPlanController::class, 'edit']);
                Route::name('update')->put('{customerPaymentPlan}', [CustomerPaymentPlanController::class, 'update']);
                Route::name('destroy')->delete('{customerPaymentPlan}', [CustomerPaymentPlanController::class, 'destroy']);
            });

            // === مسارات سجل السدادات ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/payment-history')->name('payment-history.')->group(function () {
                Route::name('list')->get('list', [CustomerPaymentHistoryController::class, 'getPaymentHistoryList']);
            });

            // === مسارات تسويات العملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/settlements')->name('settlements.')->group(function () {
                Route::name('list')->get('list', [CustomerSettlementController::class, 'getSettlementsList']);
                Route::name('create')->get('create', [CustomerSettlementController::class, 'create']);
                Route::name('store')->post('/', [CustomerSettlementController::class, 'store']);
                Route::name('edit')->get('{customerSettlement}/edit', [CustomerSettlementController::class, 'edit']);
                Route::name('update')->put('{customerSettlement}', [CustomerSettlementController::class, 'update']);
                Route::name('destroy')->delete('{customerSettlement}', [CustomerSettlementController::class, 'destroy']);
            });

            // === مسارات الرسائل النصية للعملاء ===
            Route::middleware(['can:view,customer', 'customer.subresource'])->prefix('{customer}/sms-messages')->name('sms-messages.')->group(function () {
                Route::name('create')->get('create', [CustomerSmsController::class, 'create']);
                Route::name('send')->post('send', [CustomerSmsController::class, 'send']);
                Route::name('preview-template')->post('preview-template', [CustomerSmsController::class, 'previewTemplate']);
                Route::name('destroy')->delete('{customerSms}', [CustomerSmsController::class, 'destroy']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | خطابات الشركات - Company Letters
        |--------------------------------------------------------------------------
        */
        Route::prefix('company-letters')->name('company-letters.')->group(function () {
            Route::name('index')->get('/', [CompanyLetterController::class, 'index']);
            Route::name('show')->get('{id}', [CompanyLetterController::class, 'show']);
            Route::name('preview-template')->post('{id}/preview-template', [CompanyLetterController::class, 'previewTemplate']);
            Route::name('send')->post('{id}/send', [CompanyLetterController::class, 'send']);
            Route::name('statistics')->get('statistics', [CompanyLetterController::class, 'getStatistics']);
        });

        /*
        |--------------------------------------------------------------------------
        | Message Logs
        |--------------------------------------------------------------------------
        */
        Route::prefix('communication')->name('communication.')->group(function () {
            // Message
            Route::prefix('message-logs')->name('message-logs.')->group(function () {
                Route::name('index')->get('/', [MessageLogController::class, 'index']);
                Route::name('show')->get('{messageLog}', [MessageLogController::class, 'show']);
                Route::name('destroy')->delete('{messageLog}', [MessageLogController::class, 'destroy']);
                Route::name('dashboard-stats')->get('dashboard/stats', [MessageLogController::class, 'dashboardStats']);
                Route::name('chart-data')->get('chart/data', [MessageLogController::class, 'chartData']);
                Route::name('failed-messages')->get('failed/messages', [MessageLogController::class, 'failedMessages']);
            });

            // Individual Message Logs
            Route::prefix('message-individual-logs')->name('message-individual-logs.')->group(function () {
                Route::name('index')->get('/', [MessageIndividualLogController::class, 'index']);
                Route::name('archives')->get('archives', [MessageIndividualLogController::class, 'archives']);
                Route::name('show')->get('{messageIndividualLog}', [MessageIndividualLogController::class, 'show']);
                Route::name('destroy')->delete('{messageIndividualLog}', [MessageIndividualLogController::class, 'destroy']);
                Route::name('dashboard-stats')->get('dashboard/stats', [MessageIndividualLogController::class, 'dashboardStats']);
                Route::name('chart-data')->get('chart/data', [MessageIndividualLogController::class, 'chartData']);
                Route::name('export')->post('export', [MessageIndividualLogController::class, 'export']);
                Route::name('download')->get('download/{file}', [MessageIndividualLogController::class, 'downloadFile']);
                Route::name('portfolios-by-company')->get('portfolios/by-company/{companyId}', [MessageIndividualLogController::class, 'getPortfoliosByCompany']);
                Route::name('customers-by-portfolio')->get('customers/by-portfolio/{portfolioId}', [MessageIndividualLogController::class, 'getCustomersByPortfolio']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Section Title
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
        | Archive
        |--------------------------------------------------------------------------
        */
        Route::prefix('archive')->name('archive.')->group(function () {

            // Customer Archive
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::name('index')->get('/', [CustomerArchiveController::class, 'index']);
                Route::name('restore')->patch('{customer}/restore', [CustomerArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{customer}/force-delete', [CustomerArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [CustomerArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [CustomerArchiveController::class, 'massForceDelete']);
                Route::name('get-all-ids')->get('get-all-ids', [CustomerArchiveController::class, 'getAllIds']);
            });

            // Company Archive
            Route::prefix('companies')->name('companies.')->group(function () {
                Route::name('index')->get('/', [CompanyArchiveController::class, 'index']);
                Route::name('restore')->patch('{company}/restore', [CompanyArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{company}/force-delete', [CompanyArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [CompanyArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [CompanyArchiveController::class, 'massForceDelete']);
            });

            // Contract Archive
            Route::prefix('contracts')->name('contracts.')->group(function () {
                Route::name('index')->get('/', [ContractArchiveController::class, 'index']);
                Route::name('restore')->patch('{contract}/restore', [ContractArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{contract}/force-delete', [ContractArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [ContractArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [ContractArchiveController::class, 'massForceDelete']);
                Route::name('get-all-ids')->get('get-all-ids', [ContractArchiveController::class, 'getAllIds']);
            });

            // Portfolio Archive
            Route::prefix('portfolios')->name('portfolios.')->group(function () {
                Route::name('index')->get('/', [PortfolioArchiveController::class, 'index']);
                Route::name('restore')->patch('{portfolio}/restore', [PortfolioArchiveController::class, 'restore']);
                Route::name('force-delete')->delete('{portfolio}/force-delete', [PortfolioArchiveController::class, 'forceDelete']);
                Route::name('mass-restore')->post('mass-restore', [PortfolioArchiveController::class, 'massRestore']);
                Route::name('mass-force-delete')->delete('mass-force-delete', [PortfolioArchiveController::class, 'massForceDelete']);
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
        | Financial Collections Routes
        |--------------------------------------------------------------------------
        | Routes for managing financial collections and related operations.
        */
        Route::prefix('financial-collections')->name('financial-collections.')->group(function () {

            // collections
            Route::prefix('collections')->name('collections.')->group(function () {
                Route::name('index')->get('/', [CollectionController::class, 'index']);
                Route::name('create')->get('/create', [CollectionController::class, 'create']);
                Route::name('store')->post('/', [CollectionController::class, 'store']);
                Route::name('delete-selected')->delete('/delete-selected', [CollectionController::class, 'deleteSelected']);
                Route::name('search-customers')->get('/search-customers', [CollectionController::class, 'searchCustomers']);
                Route::name('get-customer')->get('/get-customer/{customer}', [CollectionController::class, 'getCustomer']);
                Route::name('show')->get('/{collection}', [CollectionController::class, 'show']);
                Route::name('edit')->get('/{collection}/edit', [CollectionController::class, 'edit']);
                Route::name('update')->put('/{collection}', [CollectionController::class, 'update']);
                Route::name('destroy')->delete('/{collection}', [CollectionController::class, 'destroy']);
                Route::name('approve')->patch('/{collection}/approve', [CollectionController::class, 'approve']);
                Route::name('reject')->patch('/{collection}/reject', [CollectionController::class, 'reject']);
                Route::name('generate-receipt-number')->get('/generate/receipt-number', [CollectionController::class, 'generateReceiptNumber']);
                Route::name('recent-collections')->get('/recent/collections', [CollectionController::class, 'getRecentCollections']);
            });

            // admin-collections
            Route::prefix('admin-collections')->name('admin-collections.')->group(function () {
                Route::name('index')->get('/', [AdminCollectionsController::class, 'index']);
                Route::name('search-customers')->get('/search-customers', [AdminCollectionsController::class, 'searchCustomers']);
                Route::name('get-all-ids')->get('/get-all-ids', [AdminCollectionsController::class, 'getAllCollectionIds']);
                Route::name('delete-selected')->delete('/delete-selected', [AdminCollectionsController::class, 'deleteSelected']);
                Route::name('show')->get('/{collection}', [AdminCollectionsController::class, 'show']);
                Route::name('destroy')->delete('/{collection}', [AdminCollectionsController::class, 'destroy']);
                Route::name('approve')->patch('/{collection}/approve', [AdminCollectionsController::class, 'approve']);
                Route::name('reject')->patch('/{collection}/reject', [AdminCollectionsController::class, 'reject']);
                Route::name('approve-selected')->patch('/approve-selected', [AdminCollectionsController::class, 'approveSelected']);
                Route::name('reject-selected')->patch('/reject-selected', [AdminCollectionsController::class, 'rejectSelected']);
                Route::name('quick-stats')->get('/stats/quick', [AdminCollectionsController::class, 'getQuickStats']);
            });

            // collection-targets
            Route::prefix('collection-targets')->name('collection-targets.')->group(function () {
                Route::name('index')->get('/', [CollectionTargetController::class, 'index']);
                Route::name('create')->get('/create', [CollectionTargetController::class, 'create']);
                Route::name('store')->post('/', [CollectionTargetController::class, 'store']);
                Route::name('edit')->get('/{collection_target}/edit', [CollectionTargetController::class, 'edit']);
                Route::name('update')->put('/{collection_target}', [CollectionTargetController::class, 'update']);
                Route::name('destroy')->delete('/{collection_target}', [CollectionTargetController::class, 'destroy']);
                Route::name('toggle-status')->patch('/{collection_target}/toggle-status', [CollectionTargetController::class, 'toggleStatus']);
                Route::name('user-details')->get('/user-details/{userId}', [CollectionTargetController::class, 'showUserDetails']);
            });

            // import
            Route::prefix('import')->name('import.')->group(function () {
                Route::name('show')->get('/', [CollectionImportController::class, 'showImportForm']);
                Route::name('download-template')->get('/download-template', [CollectionImportController::class, 'generateTemplate']);
                Route::name('upload-analyze')->post('/upload-analyze', [CollectionImportController::class, 'uploadAndAnalyze']);
                Route::name('process')->post('/process', [CollectionImportController::class, 'processImport']);
                Route::name('get-progress')->get('/get-progress', [CollectionImportController::class, 'getImportProgress']);
                Route::name('download-error-report')->get('/download-error-report', [CollectionImportController::class, 'downloadErrorReport']);
                Route::name('cancel')->post('/cancel', [CollectionImportController::class, 'cancelImport']);
            });

            // reconciliation
            Route::prefix('reconciliation')->name('reconciliation.')->group(function () {
                Route::name('index')->get('/', [PaymentReconciliationController::class, 'index']);
                Route::name('get-portfolios')->post('/get-portfolios', [PaymentReconciliationController::class, 'getPortfolios']);
                Route::name('analyze-headers')->post('/analyze-headers', [PaymentReconciliationController::class, 'analyzeHeaders']);
                Route::name('store-temp-file')->post('/store-temp-file', [PaymentReconciliationController::class, 'storeTemporaryFile']);
                Route::name('process')->post('/process', [PaymentReconciliationController::class, 'process']);
                Route::name('get-progress')->get('/get-progress', [PaymentReconciliationController::class, 'getProgress']);
                Route::get('/{reconciliation}/reports/summary', [PaymentReconciliationController::class, 'downloadSummaryReport'])->name('reports.summary');
                Route::get('/{reconciliation}/reports/unmatched', [PaymentReconciliationController::class, 'downloadUnmatchedReport'])->name('reports.unmatched');
                Route::get('/{reconciliation}/reports/discrepancies', [PaymentReconciliationController::class, 'downloadDiscrepanciesReport'])->name('reports.discrepancies');
                Route::get('/{reconciliation}/reports/system-only', [PaymentReconciliationController::class, 'downloadSystemOnlyReport'])->name('reports.system-only');
                Route::get('/{reconciliation}/reports/employee-performance', [PaymentReconciliationController::class, 'downloadEmployeePerformanceReport'])->name('reports.employee-performance');
                Route::get('/{reconciliation}/reports/errors', [PaymentReconciliationController::class, 'downloadErrorsReport'])->name('reports.errors');
            });
        });

        /*
        |--------------------------------------------------------------------------
        | عمليات التحصيل - Collection Process Routes
        |--------------------------------------------------------------------------
        */
        require __DIR__.'/collection-process.php';

        /*
        |--------------------------------------------------------------------------
        | Export Routes - راوتات التصدير منفصلة
        |--------------------------------------------------------------------------
        | راوتات التصدير مفصولة عن العمليات الأساسية لسهولة الإدارة
        */
        Route::prefix('exports')->name('exports.')->group(function () {
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::name('export')->post('/export', [CustomerExportController::class, 'exportCustomers']);
                Route::name('check-progress')->post('/check-progress', [CustomerExportController::class, 'checkExportProgress']);
                Route::name('download')->get('/download/{file}', [CustomerExportController::class, 'downloadExportFile']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Reports Routes - مسارات التقارير
        |--------------------------------------------------------------------------
        */
        Route::prefix('reports')->name('reports.')->group(function () {

            // العملاء
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::name('index')->get('/', [CustomerReportController::class, 'index']);
                Route::name('export.pdf')->post('/export-pdf', [CustomerReportController::class, 'exportPDF']);
                Route::name('export.excel')->post('/export-excel', [CustomerReportController::class, 'exportExcel']);
                Route::name('show-export-modal')->post('/show-export-modal', [CustomerReportController::class, 'showExportFieldsModal']);
                Route::name('check-progress')->post('/check-progress', [CustomerReportController::class, 'checkExportProgress']);
                Route::name('download')->get('/download/{file}', [CustomerReportController::class, 'downloadFile']);
                Route::name('check.count')->post('/check-count', [CustomerReportController::class, 'checkRecordsCount']);
            });

            // متابعات العملاء
            Route::prefix('customer-follows')->name('customer_follows.')->group(function () {
                Route::name('index')->get('/', [CustomerFollowReportController::class, 'index']);
                Route::name('search-customers')->get('/search-customers', [CustomerFollowReportController::class, 'searchCustomers']);
                Route::name('get-customer')->get('/get-customer/{customer}', [CustomerFollowReportController::class, 'getCustomer']);
                Route::name('export.pdf')->post('/export-pdf', [CustomerFollowReportController::class, 'exportPDF']);
                Route::name('export.excel')->post('/export-excel', [CustomerFollowReportController::class, 'exportExcel']);
                Route::name('show-export-modal')->post('/show-export-modal', [CustomerFollowReportController::class, 'showExportFieldsModal']);
                Route::name('check-progress')->post('/check-progress', [CustomerFollowReportController::class, 'checkExportProgress']);
                Route::name('download')->get('/download/{file}', [CustomerFollowReportController::class, 'downloadFile']);
                Route::name('check.count')->post('/check-count', [CustomerFollowReportController::class, 'checkRecordsCount']);
                Route::name('cancel-export')->post('/cancel-export', [CustomerFollowReportController::class, 'cancelExport']);
                Route::name('portfolios-by-company')->get('/portfolios-by-company', [CustomerFollowReportController::class, 'getPortfoliosByCompany']);
            });

            // التحصيل
            Route::prefix('financial')->name('financial.')->group(function () {
                Route::name('index')->get('/', [FinancialReportController::class, 'index']);
                Route::name('export')->post('/export', [FinancialReportController::class, 'export']);
                Route::name('export.pdf')->post('/export-pdf', [FinancialReportController::class, 'exportPDF']);
                Route::name('show-export-modal')->post('/show-export-modal', [FinancialReportController::class, 'showExportFieldsModal']);
                Route::name('export.excel')->post('/export-excel', [FinancialReportController::class, 'exportExcel']);
                Route::name('check-records-count')->get('/check-records-count', [FinancialReportController::class, 'checkRecordsCount']);
                Route::name('download')->get('/download/{file}', [FinancialReportController::class, 'downloadFile']);
                Route::name('search-customers')->get('/search-customers', [FinancialReportController::class, 'searchCustomers']);
                Route::name('get-customer')->get('/get-customer/{customer}', [FinancialReportController::class, 'getCustomer']);
                Route::name('check-progress')->post('/check-progress', [FinancialReportController::class, 'checkExportProgress']);
                Route::name('portfolios-by-company')->get('/portfolios-by-company', [FinancialReportController::class, 'getPortfoliosByCompany']);
            });

            // الشركات و المحافظ
            Route::prefix('companies')->name('companies.')->group(function () {
                Route::name('index')->get('/', [CompanyReportController::class, 'index']);
                Route::name('stats')->get('{company}/stats', [CompanyReportController::class, 'companyStats']);
                Route::name('portfolios')->get('{company}/portfolios', [CompanyReportController::class, 'companyPortfolios']);
                Route::name('portfolio')->get('portfolio/{portfolio}/stats', [CompanyReportController::class, 'portfolioStats']);
                Route::name('show-export-modal')->post('/show-export-modal', [CompanyReportController::class, 'showExportFieldsModal']);
                Route::name('export.excel')->post('/export-excel', [CompanyReportController::class, 'exportExcel']);
                Route::name('check-progress')->post('/check-progress', [CompanyReportController::class, 'checkExportProgress']);
                Route::name('download')->get('/download/{file}', [CompanyReportController::class, 'downloadFile']);
            });

            Route::prefix('employees')->name('employees.')->group(function () {
                Route::name('index')->get('/', [EmployeePerformanceReportController::class, 'index']);
                Route::name('export.pdf')->post('/export-pdf', [EmployeePerformanceReportController::class, 'exportPDF']);
                Route::name('export.excel')->post('/export-excel', [EmployeePerformanceReportController::class, 'exportExcel']);
                Route::name('show-export-modal')->post('/show-export-modal', [EmployeePerformanceReportController::class, 'showExportFieldsModal']);
                Route::name('check-progress')->post('/check-progress', [EmployeePerformanceReportController::class, 'checkExportProgress']);
                Route::name('download')->get('/download/{file}', [EmployeePerformanceReportController::class, 'downloadFile']);
                Route::name('check.count')->post('/check-count', [EmployeePerformanceReportController::class, 'checkRecordsCount']);
                Route::name('stats')->post('stats', [EmployeePerformanceReportController::class, 'getFilteredStats']);
            });
            // });

            // التقارير التنفيذية
            Route::prefix('executive')->name('executive.')->group(function () {
                Route::name('index')->get('/', [ExecutiveReportController::class, 'index']);
                Route::name('data')->get('/data', [ExecutiveReportController::class, 'data']);
                Route::name('export-excel')->get('/export-excel', [ExecutiveReportController::class, 'exportExcel']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Bulk Delete Route
        |--------------------------------------------------------------------------
        | bulk delete route for all the resources
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

require __DIR__.'/tenant-auth.php';
