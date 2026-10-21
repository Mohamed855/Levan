<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\Assignments\AssignmentController;
use App\Http\Controllers\Tenant\Auctions\AuctionController;
use App\Http\Controllers\Tenant\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\Auth\NewPasswordController;
use App\Http\Controllers\Tenant\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenant\Properties\PropertyController;
use App\Http\Controllers\Tenant\Roles\TenantRoleController;
use App\Http\Controllers\Tenant\Users\TenantUserController;
use App\Http\Controllers\Tenant\Dashboard\DashboardController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:tenant')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:tenant')->name('logout');

Route::middleware('auth:tenant')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function (): void {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
    });
    Route::get('auctions/{auction}/preview/{type}', [AuctionController::class, 'preview'])->name('auctions.preview');
    Route::resource('users', TenantUserController::class);
    Route::patch('users/{user}/toggle-status', [TenantUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('roles', TenantRoleController::class);
    Route::resource('auctions', AuctionController::class)->except(['destroy']);
    Route::resource('assignments', AssignmentController::class)->except(['destroy']);
    Route::patch('assignments/{assignment}/toggle-status', [AssignmentController::class, 'toggleStatus'])->name('assignments.toggle-status');
    Route::resource('properties', PropertyController::class)->except(['destroy']);
    Route::patch('properties/{property}/toggle-status', [PropertyController::class, 'toggleStatus'])->name('properties.toggle-status');
    Route::resource('companies', \App\Http\Controllers\Tenant\Companies\CompanyController::class);
    Route::patch('companies/{company}/toggle-status', [\App\Http\Controllers\Tenant\Companies\CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
    Route::get('bills', [\App\Http\Controllers\Tenant\Bills\TenantBillController::class, 'index'])->name('bills.index');
    Route::get('bills/{bill}', [\App\Http\Controllers\Tenant\Bills\TenantBillController::class, 'show'])->name('bills.show');
    Route::get('bills/{bill}/bank-transfer', [\App\Http\Controllers\Tenant\Bills\TenantBillController::class, 'bankTransfer'])->name('bills.bank-transfer');
    Route::post('bills/{bill}/upload-receipt', [\App\Http\Controllers\Tenant\Bills\TenantBillController::class, 'uploadReceipt'])->name('bills.upload-receipt');
    Route::resource('tickets', \App\Http\Controllers\Tenant\Tickets\TenantTicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Tenant\Tickets\TenantTicketController::class, 'reply'])->name('tickets.reply');
    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/mark-as-read', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'destroy'])->name('destroy');
        Route::post('/mass-delete', [\App\Http\Controllers\Tenant\Notifications\TenantNotificationController::class, 'massDelete'])->name('mass-delete');
    });
    Route::get('settings', [\App\Http\Controllers\Tenant\Settings\TenantSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [\App\Http\Controllers\Tenant\Settings\TenantSettingController::class, 'update'])->name('settings.update');
    Route::get('activity-log', [\App\Http\Controllers\Tenant\ActivityLog\TenantActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('activity-log/{id}', [\App\Http\Controllers\Tenant\ActivityLog\TenantActivityLogController::class, 'show'])->name('activity-log.show');

    Route::prefix('archives')->name('archives.')->group(function (): void {
        Route::prefix('properties')->name('properties.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Tenant\Archive\Properties\PropertyArchiveController::class, 'index'])->name('index');
            Route::patch('/{id}/restore', [\App\Http\Controllers\Tenant\Archive\Properties\PropertyArchiveController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [\App\Http\Controllers\Tenant\Archive\Properties\PropertyArchiveController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('assignments')->name('assignments.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Tenant\Archive\Assignments\AssignmentArchiveController::class, 'index'])->name('index');
            Route::patch('/{id}/restore', [\App\Http\Controllers\Tenant\Archive\Assignments\AssignmentArchiveController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [\App\Http\Controllers\Tenant\Archive\Assignments\AssignmentArchiveController::class, 'forceDelete'])->name('force-delete');
        });

        Route::prefix('companies')->name('companies.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Tenant\Archive\Companies\CompanyArchiveController::class, 'index'])->name('index');
            Route::patch('/{id}/restore', [\App\Http\Controllers\Tenant\Archive\Companies\CompanyArchiveController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [\App\Http\Controllers\Tenant\Archive\Companies\CompanyArchiveController::class, 'forceDelete'])->name('force-delete');
        });
    });
});


