<?php

use App\Http\Controllers\Admin\ActivityLog\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminMangment\Roles\AdminRoleController;
use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\AuctionTemplates\AuctionTemplateController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController as AdminUserAdminUserController;
use App\Http\Controllers\Admin\Archive\Bill\BillArchiveController;
use App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController;
use App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController;
use App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
use App\Http\Controllers\Admin\Clients\ClientController;
use App\Http\Controllers\Admin\Coupons\AdminCouponController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Admin\Settings\Bank\SettingsBankController;
use App\Http\Controllers\Admin\Settings\Domain\SettingsDomainController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\SubscriptionManagement\SubscriptionManagementController;
use App\Http\Controllers\Admin\Subscriptions\SubscriptionController as CentralSubscriptionController;
use App\Http\Controllers\Admin\Tickets\AdminTicketController;
use App\Http\Controllers\Admin\PublicRequests\ContactRequestController;
use App\Http\Controllers\Admin\PublicRequests\TrialRequestController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use Illuminate\Support\Facades\Route;

Route::name('password.reset')->get('reset-password/{token}', [NewPasswordController::class, 'create']);

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.active', 'admin.2fa.force'])->group(function () {
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function () {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::match(['post', 'put', 'patch'], '/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
        Route::delete('file', [WizardDraftController::class, 'destroyFile'])->name('file.destroy');
        Route::match(['post', 'put', 'patch'], 'validate-step', [WizardDraftController::class, 'validateStep'])->name('validate-step');
    });

    Route::name('password.store')->post('reset-password', [NewPasswordController::class, 'updatePassword']);

    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('dashboard/platform-activity', [DashboardController::class, 'platformActivity'])->name('dashboard.platform-activity');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::prefix('banks')->name('banks.')->group(function () {
            Route::resource('/', SettingsBankController::class)->except('show')->parameters(['' => 'bank']);
            Route::patch('{bank}/toggle-status', [SettingsBankController::class, 'toggleStatus'])->name('toggle-status');
        });

        Route::prefix('domains')->name('domains.')->group(function () {
            Route::resource('/', SettingsDomainController::class)->except('show')->parameters(['' => 'domain']);
            Route::patch('{domain}/toggle-status', [SettingsDomainController::class, 'toggleStatus'])->name('toggle-status');
        });

        // اعدادات النظاام
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    // تنبيهات الاشتراكات
    Route::prefix('subscription-manage')->name('subscriptionManage.')->group(function () {
        Route::middleware('can:subscriptions.pending.view')->group(function () {
            Route::get('pending', [SubscriptionManagementController::class, 'pending'])->name('pending');
            Route::get('expiring', [SubscriptionManagementController::class, 'expiring'])->name('expiring');
            Route::get('expired', [SubscriptionManagementController::class, 'expired'])->name('expired');
        });

        Route::middleware('can:subscriptions.pending.approve')->group(function () {
            Route::post('{subscription}/accept', [SubscriptionManagementController::class, 'accept'])->name('accept');
            Route::post('mass-accept', [SubscriptionManagementController::class, 'massAccept'])->name('mass-accept');
        });

        Route::middleware('can:subscriptions.pending.reject')->group(function () {
            Route::post('{subscription}/reject', [SubscriptionManagementController::class, 'reject'])->name('reject');
            Route::post('mass-reject', [SubscriptionManagementController::class, 'massReject'])->name('mass-reject');
        });
    });

    // ادارة العملاء (Clients)
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::post('check-commercial-number-unique', [ClientController::class, 'checkCommercialNumberUnique'])->name('check-commercial-number-unique');
        Route::post('check-company-name-unique', [ClientController::class, 'checkCompanyNameUnique'])->name('check-company-name-unique');
        Route::post('check-tax-number-unique', [ClientController::class, 'checkTaxNumberUnique'])->name('check-tax-number-unique');
        Route::post('check-admin-email-unique', [ClientController::class, 'checkAdminEmailUnique'])->name('check-admin-email-unique');
        Route::post('check-admin-phone-unique', [ClientController::class, 'checkAdminPhoneUnique'])->name('check-admin-phone-unique');
        Route::get('plans/{plan}/duration', [ClientController::class, 'planDuration'])->name('plan-duration');
        Route::resource('/', ClientController::class)->parameters(['' => 'client']);
    });

    // Backward compatibility aliases for companies
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::post('check-commercial-number-unique', [ClientController::class, 'checkCommercialNumberUnique'])->name('check-commercial-number-unique');
        Route::post('check-company-name-unique', [ClientController::class, 'checkCompanyNameUnique'])->name('check-company-name-unique');
        Route::post('check-tax-number-unique', [ClientController::class, 'checkTaxNumberUnique'])->name('check-tax-number-unique');
        Route::post('check-admin-email-unique', [ClientController::class, 'checkAdminEmailUnique'])->name('check-admin-email-unique');
        Route::post('check-admin-phone-unique', [ClientController::class, 'checkAdminPhoneUnique'])->name('check-admin-phone-unique');
        Route::get('plans/{plan}/duration', [ClientController::class, 'planDuration'])->name('plan-duration');
        Route::resource('/', ClientController::class)->parameters(['' => 'client']);
    });


    // ادارة الاشتراكات
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::patch('{subscription}/toggle-status', [CentralSubscriptionController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('plans/{plan}/duration', [CentralSubscriptionController::class, 'planDuration'])->name('plan-duration');
        Route::get('companies/{company}/details', [CentralSubscriptionController::class, 'companyDetails'])->name('company-details');
        Route::resource('/', CentralSubscriptionController::class)
            ->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['' => 'subscription']);
    });
    // الكوبونات
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::patch('{coupon}/toggle-status', [AdminCouponController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('details/{coupon}', [AdminCouponController::class, 'getCouponDetails'])->name('details');
        Route::resource('/', AdminCouponController::class)->except('show')->parameters(['' => 'coupon']);
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('bulk-delete', [AdminUserController::class, 'bulkDelete'])->name('bulk-delete');
        Route::patch('{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('profile/{id}', [AdminUserController::class, 'showProfile'])->name('profile');
        Route::resource('/', AdminUserController::class)->except('show')->parameters(['' => 'user']);

        // check if email is unique via ajax
        Route::post('check-email-unique', [AdminUserController::class, 'checkEmailUnique'])->name('check-email-unique');

        Route::post('check-name-unique', [AdminUserController::class, 'checkNameUnique'])
            ->name('check-name-unique');

        Route::post('check-phone-unique', [AdminUserController::class, 'checkPhoneUnique'])
            ->name('check-phone-unique');
    });

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::resource('/', AdminRoleController::class)->except('show')->parameters(['' => 'role']);
        Route::get('get-user-roles/{id}', [AdminRoleController::class, 'getUserRoles'])->name('get-user-roles'); // جلب الصلاحيات
        Route::post('update-user-roles/{id}', [AdminRoleController::class, 'updateUserRoles'])->name('update-user-role'); // تعديل الصلاحية للمستخدم
    });

    // Activity Logs
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::resource('/', AdminActivityLogController::class)
            ->only(['index', 'show'])
            ->parameters(['' => 'id']);
    });

    // Plans Management
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::post('update-sort-order', [PlanController::class, 'updateSortOrder'])->name('update-sort-order');
        Route::post('reorder', [PlanController::class, 'updateSortOrder'])->name('reorder');
        Route::patch('{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('toggle-active');
        Route::get('details/{plan}', [PlanController::class, 'show'])->name('details');
        Route::resource('/', PlanController::class)->parameters(['' => 'plan']);
    });

    // Auction Templates Management
    Route::prefix('auction-templates')->name('auction-templates.')->group(function () {
        Route::patch('{auction_template}/toggle-status', [AuctionTemplateController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('{auction_template}/fields', [AuctionTemplateController::class, 'showFields'])->name('fields');
        Route::resource('/', AuctionTemplateController::class)->parameters(['' => 'auction_template']);
    });
    Route::resource('faqs', FaqController::class)->except('show');

    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/{bill}/print', [AdminBillsController::class, 'print'])->name('print');
        Route::post('/{bill}/verify', [AdminBillsController::class, 'verify'])->name('verify');
        Route::post('/{bill}/reject', [AdminBillsController::class, 'reject'])->name('reject');
        Route::post('{bill}/reactivate', [AdminBillsController::class, 'reactivate'])->name('reactivate');
        Route::resource('/', AdminBillsController::class)
            ->only(['index', 'show'])
            ->parameters(['' => 'bill']);
    });

    // Tickets Management
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::post('/toggle-service', [AdminTicketController::class, 'toggleService'])->name('toggleService');
        Route::post('/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('updateStatus');
        Route::resource('/', AdminTicketController::class)
            ->only(['index', 'show'])
            ->parameters(['' => 'ticket']);
    });

    Route::prefix('contact-requests')->name('contact-requests.')->group(function () {
        Route::get('/', [ContactRequestController::class, 'index'])->name('index');
        Route::get('{contactRequest}', [ContactRequestController::class, 'show'])->name('show');
        Route::post('{contactRequest}/reply', [ContactRequestController::class, 'reply'])->name('reply');
    });

    Route::prefix('trial-requests')->name('trial-requests.')->group(function () {
        Route::get('/', [TrialRequestController::class, 'index'])->name('index');
        Route::get('{trialRequest}', [TrialRequestController::class, 'show'])->name('show');
        Route::post('{trialRequest}/approve', [TrialRequestController::class, 'approve'])->name('approve');
        Route::post('{trialRequest}/reject', [TrialRequestController::class, 'reject'])->name('reject');
    });

    // super admin archive routes
    Route::prefix('archives')->name('archives.')->group(function () {
        Route::get('/', [AdminUserAdminUserController::class, 'index'])->name('index');
        Route::patch('{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::patch('/restore/{id}', [AdminUserAdminUserController::class, 'restore'])->name('restore');
        Route::delete('/force-delete/{id}', [AdminUserAdminUserController::class, 'forceDelete'])->name('force-delete');

        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [PlanArchiveController::class, 'index'])->name('index');
            Route::patch('/restore/{id}', [PlanArchiveController::class, 'restore'])->name('restore');
            Route::delete('/force-delete/{id}', [PlanArchiveController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('coupons')->name('coupons.')->group(function () {
            Route::get('/', [CouponArchiveController::class, 'index'])->name('index');
            Route::patch('/restore/{id}', [CouponArchiveController::class, 'restore'])->name('restore');
            Route::delete('/force-delete/{id}', [CouponArchiveController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/', [TenantArchiveController::class, 'index'])->name('index');
            Route::patch('/restore/{id}', [TenantArchiveController::class, 'restore'])->name('restore');
            Route::delete('/force-delete/{id}', [TenantArchiveController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', [BillArchiveController::class, 'index'])->name('index');
            Route::patch('/restore/{id}', [BillArchiveController::class, 'restore'])->name('restore');
            Route::delete('/force-delete/{id}', [BillArchiveController::class, 'forceDelete'])->name('force-delete');
            Route::get('/{bill}', [AdminBillsController::class, 'show'])->name('show');
        });
    });

    Route::name('mass-delete')->post('/mass-delete', [AdminMassDeleteController::class, 'massDelete']);
});

require __DIR__.'/admin-auth.php';
