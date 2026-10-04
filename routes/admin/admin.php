<?php

use App\Http\Controllers\Admin\ActivityLog\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminMangment\Roles\AdminRoleController;
use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController as AdminUserAdminUserController;
use App\Http\Controllers\Admin\Archive\Bill\BillArchiveController;
use App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController;
use App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController;
use App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
use App\Http\Controllers\Admin\Companies\CompanyController;
use App\Http\Controllers\Admin\Subscriptions\SubscriptionController as CentralSubscriptionController;
use App\Http\Controllers\Admin\ContentManagement\Client\ClientController;
use App\Http\Controllers\Admin\ContentManagement\Portfolio\PortfolioController;
use App\Http\Controllers\Admin\Coupons\AdminCouponController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Admin\Settings\Bank\SettingsBankController;
use App\Http\Controllers\Admin\Settings\Domain\SettingsDomainController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\SubscriptionManagement\SubscriptionManagementController;
use App\Http\Controllers\Admin\TenantMangment\TenantMangmentController;
use App\Http\Controllers\Admin\TransferRequest\TransferRequestController;
use App\Http\Controllers\Admin\Versions\VersionController;
use App\Http\Controllers\Admin\VersionTester\VersionTesterController;
use App\Http\Controllers\Admin\Tickets\AdminTicketController;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;

Route::name('password.reset')->get('reset-password/{token}', [NewPasswordController::class, 'create']);

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.active', 'admin.2fa.force'])->group(function () {
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function () {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
        Route::post('validate-step', [WizardDraftController::class, 'validateStep'])->name('validate-step');
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

    // ادارة العملاء
    // Route::prefix('tenant-manage')->name('tenantManage.')->group(function () {
    //     Route::get('resendEmail/{row}', [TenantMangmentController::class, 'resendEmail'])->name('resendEmail');
    //     Route::resource('/', TenantMangmentController::class)->parameters(['' => 'tenant']);
    // });

    // تنبيهات الاشتراكات
    Route::prefix('subscription-manage')->name('subscriptionManage.')->group(function () {
        Route::get('pending', [SubscriptionManagementController::class, 'pending'])->name('pending');
        Route::get('expiring', [SubscriptionManagementController::class, 'expiring'])->name('expiring');
        Route::get('expired', [SubscriptionManagementController::class, 'expired'])->name('expired');
        Route::post('{subscription}/accept', [SubscriptionManagementController::class, 'accept'])->name('accept');
        Route::post('{subscription}/reject', [SubscriptionManagementController::class, 'reject'])->name('reject');
        Route::post('mass-accept', [SubscriptionManagementController::class, 'massAccept'])->name('mass-accept');
        Route::post('mass-reject', [SubscriptionManagementController::class, 'massReject'])->name('mass-reject');
    });

    // ادارة الشركات
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::post('check-commercial-number-unique', [CompanyController::class, 'checkCommercialNumberUnique'])->name('check-commercial-number-unique');
        Route::post('check-company-name-unique', [CompanyController::class, 'checkCompanyNameUnique'])->name('check-company-name-unique');
        Route::post('check-tax-number-unique', [CompanyController::class, 'checkTaxNumberUnique'])->name('check-tax-number-unique');
        Route::post('check-admin-email-unique', [CompanyController::class, 'checkAdminEmailUnique'])->name('check-admin-email-unique');
        Route::post('check-admin-phone-unique', [CompanyController::class, 'checkAdminPhoneUnique'])->name('check-admin-phone-unique');
        Route::get('plans/{plan}/duration', [CompanyController::class, 'planDuration'])->name('plan-duration');
        Route::resource('/', CompanyController::class)->parameters(['' => 'company']);
    });

    // ادارة الاشتراكات
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [CentralSubscriptionController::class, 'index'])->name('index');
        Route::get('create', [CentralSubscriptionController::class, 'create'])->name('create');
        Route::post('/', [CentralSubscriptionController::class, 'store'])->name('store');
        Route::delete('{subscription}', [CentralSubscriptionController::class, 'destroy'])->name('destroy');
        Route::patch('{subscription}/toggle-status', [CentralSubscriptionController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('plans/{plan}/duration', [CentralSubscriptionController::class, 'planDuration'])->name('plan-duration');
        Route::get('companies/{company}/details', [CentralSubscriptionController::class, 'companyDetails'])->name('company-details');
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

    //Activity Logs
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [AdminActivityLogController::class, 'index'])->name('index');
        Route::get('{id}', [AdminActivityLogController::class, 'show'])->name('show');
    });

    // transfers requests management
    // Route::prefix('transfer-requests')->name('transfer-requests.')->group(function () {
    //     Route::get('/', [TransferRequestController::class, 'index'])->name('index');
    //     Route::get('/details/{subscription}', [TransferRequestController::class, 'details'])->name('details');
    //     Route::post('{subscription}/approve', [TransferRequestController::class, 'approveTransferRequest'])->name('approve');
    //     Route::post('{subscription}/reject', [TransferRequestController::class, 'rejectTransferRequest'])->name('reject');
    // });

    // Plans Management
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::post('update-sort-order', [PlanController::class, 'updateSortOrder'])->name('update-sort-order');
        Route::post('reorder', [PlanController::class, 'updateSortOrder'])->name('reorder');
        Route::patch('{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('toggle-active');
        Route::get('details/{plan}', [PlanController::class, 'show'])->name('details');
        Route::resource('/', PlanController::class)->parameters(['' => 'plan']);
    });

    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/', [AdminBillsController::class, 'index'])->name('index');
        Route::get('/{bill}', [AdminBillsController::class, 'show'])->name('show');
        Route::get('/{bill}/print', [AdminBillsController::class, 'print'])->name('print');
        Route::post('/{bill}/verify', [AdminBillsController::class, 'verify'])->name('verify');
        Route::post('/{bill}/reject', [AdminBillsController::class, 'reject'])->name('reject');
        Route::post('{bill}/reactivate', [AdminBillsController::class, 'reactivate'])->name('reactivate');
    });

    // Tickets Management
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [AdminTicketController::class, 'index'])->name('index');
        Route::post('/toggle-service', [AdminTicketController::class, 'toggleService'])->name('toggleService');
        Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('updateStatus');
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

    // Versions Routes
    Route::prefix('versions')->name('versions.')->group(function () {
        Route::resource('/', VersionController::class)->parameters(['' => 'version']);
        Route::patch('{version}/toggle-status', [VersionController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Versions Tester Routes
    Route::prefix('version_testers')->name('version_testers.')->group(function () {
        Route::resource('/', VersionTesterController::class)->except('show')->parameters(['' => 'version_tester']);
        Route::patch('{version_tester}/toggle-status', [VersionTesterController::class, 'toggleStatus'])->name('toggle-status');
    });
    Route::name('mass-delete')->post('/mass-delete', [MassDeleteController::class, 'massDelete']);

    Route::prefix('content-management')->name('content_management.')->group(function () {
        Route::prefix('client')->name('client.')->group(function () {
            Route::resource('/', ClientController::class)->parameters(['' => 'client']);
            Route::patch('/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('toggle-status');
        });

        Route::prefix('portfolio')->name('portfolio.')->group(function () {
            Route::resource('/', PortfolioController::class)->parameters(['' => 'portfolio']);
            Route::patch('/{portfolio}/toggle-status', [PortfolioController::class, 'toggleStatus'])->name('toggle-status');
        });
    });
});

require __DIR__ . '/admin-auth.php';
