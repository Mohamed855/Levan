<?php

use App\Http\Controllers\Admin\ActivityLog\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminMangment\Roles\AdminRoleController;
use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController as AdminUserAdminUserController;
use App\Http\Controllers\Admin\Archive\Bill\BillArchiveController;
use App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController;
use App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController;
use App\Http\Controllers\Admin\Archive\Subscription\SubscriptionArchiveController;
use App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
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

Route::name('password.reset')->get('reset-password/{token}', [NewPasswordController::class, 'create']);

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.active', 'admin.2fa.force'])->group(function () {
    Route::name('password.store')->post('reset-password', [NewPasswordController::class, 'updatePassword']);

    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::prefix('banks')->name('banks.')->group(function () {
            Route::resource('/', SettingsBankController::class)->parameters(['' => 'bank']);
        });

        Route::prefix('domains')->name('domains.')->group(function () {
            Route::resource('/', SettingsDomainController::class)->parameters(['' => 'domain']);
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

    // ادارة الاشتراكات
    Route::prefix('subscription-manage')->name('subscriptionManage.')->group(function () {
        Route::get('tenant/{tenant}/access', [SubscriptionManagementController::class, 'access'])->name('tenant-access');
        Route::resource('/', SubscriptionManagementController::class)->parameters(['' => 'subscription']);
    });

    // الكوبونات
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('details/{coupon}', [AdminCouponController::class, 'getCouponDetails'])->name('details');
        Route::resource('/', AdminCouponController::class)->parameters(['' => 'coupon']);
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::patch('{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('profile/{id}', [AdminUserController::class, 'showProfile'])->name('profile');
        Route::resource('/', AdminUserController::class)->parameters(['' => 'user']);

        // check if email is unique via ajax
        Route::post('check-email-unique', [AdminUserController::class, 'checkEmailUnique'])->name('check-email-unique');

        Route::post('check-name-unique', [AdminUserController::class, 'checkNameUnique'])
            ->name('check-name-unique');

        Route::post('check-phone-unique', [AdminUserController::class, 'checkPhoneUnique'])
            ->name('check-phone-unique');
    });

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::resource('/', AdminRoleController::class)->parameters(['' => 'role']);
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
        Route::get('/create', [AdminUserAdminUserController::class, 'create'])->name('create');
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

        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [SubscriptionArchiveController::class, 'index'])->name('index');
            Route::patch('/restore/{id}', [SubscriptionArchiveController::class, 'restore'])->name('restore');
            Route::delete('/force-delete/{id}', [SubscriptionArchiveController::class, 'forceDelete'])->name('force-delete');
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
    });

    // Versions Tester Routes
    Route::prefix('version_testers')->name('version_testers.')->group(function () {
        Route::resource('/', VersionTesterController::class)->parameters(['' => 'version_tester']);
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
