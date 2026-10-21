<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuctionTemplates\AuctionTemplateController;
use App\Http\Controllers\Admin\Banks\BankController;
use App\Http\Controllers\Admin\Bills\BillController;
use App\Http\Controllers\Admin\Coupons\CouponController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Admin\PublicRequests\PublicRequestController;
use App\Http\Controllers\Admin\Roles\AdminRoleController;
use App\Http\Controllers\Admin\Settings\SettingController;
use App\Http\Controllers\Admin\Subscriptions\SubscriptionController;
use App\Http\Controllers\Admin\Tenants\TenantController;
use App\Http\Controllers\Admin\Tickets\TicketController;
use App\Http\Controllers\Admin\Users\AdminUserController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function (): void {
    Route::post('mass-delete', [AdminMassDeleteController::class, 'massDelete'])->name('mass-delete');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function (): void {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
    });
    Route::resource('auction-templates', AuctionTemplateController::class)->parameters(['auction-templates' => 'auctionTemplate']);
    Route::patch('auction-templates/{auctionTemplate}/toggle-status', [AuctionTemplateController::class, 'toggleStatus'])->name('auction-templates.toggle-status');
    Route::post('auction-templates/{auctionTemplate}/versions/{version}/publish', [AuctionTemplateController::class, 'publish'])->name('auction-templates.versions.publish');
    Route::resource('plans', PlanController::class);
    Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
    Route::resource('tenants', TenantController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'show', 'destroy']);
    Route::patch('subscriptions/{subscription}/status', [SubscriptionController::class, 'updateStatus'])->name('subscriptions.status');
    Route::resource('bills', BillController::class)->only(['index', 'show']);
    Route::patch('bills/{bill}/status', [BillController::class, 'updateStatus'])->name('bills.status');
    Route::resource('tickets', TicketController::class)->only(['index', 'show', 'destroy']);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::resource('coupons', CouponController::class)->except(['show']);

    Route::patch('coupons/{coupon}/toggle-status', [CouponController::class, 'toggle'])->name('coupons.toggle-status');
    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-status', [AdminUserController::class, 'toggle'])->name('users.toggle-status');
    Route::resource('roles', AdminRoleController::class);
    Route::get('contact-requests', [PublicRequestController::class, 'contacts'])->name('contact-requests.index');
    Route::get('contact-requests/{contactRequest}', [PublicRequestController::class, 'contact'])->name('contact-requests.show');
    Route::post('contact-requests/{contactRequest}/reply', [PublicRequestController::class, 'reply'])->name('contact-requests.reply');
    Route::get('trial-requests', [PublicRequestController::class, 'trials'])->name('trial-requests.index');
    Route::post('trial-requests/{trialRequest}/approve', [PublicRequestController::class, 'approve'])->name('trial-requests.approve');
    Route::post('trial-requests/{trialRequest}/reject', [PublicRequestController::class, 'reject'])->name('trial-requests.reject');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::resource('banks', BankController::class)->except(['show']);
    Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus'])->name('banks.toggle-status');
    Route::resource('control-domains', \App\Http\Controllers\Admin\ControlDomains\ControlDomainController::class)->parameters(['control-domains' => 'controlDomain'])->except(['show']);
    Route::patch('control-domains/{controlDomain}/toggle-status', [\App\Http\Controllers\Admin\ControlDomains\ControlDomainController::class, 'toggleStatus'])->name('control-domains.toggle-status');
    Route::resource('faqs', \App\Http\Controllers\Admin\Faqs\FaqController::class)->except(['show']);
    Route::patch('faqs/{faq}/toggle-status', [\App\Http\Controllers\Admin\Faqs\FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');
    Route::resource('activity-logs', \App\Http\Controllers\Admin\ActivityLogs\AdminActivityLogController::class)->parameters(['activity-logs' => 'activityLog'])->only(['index', 'show', 'destroy']);

    Route::prefix('archives')->name('archives.')->group(function (): void {
        Route::prefix('plans')->name('plans.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\Archive\Plans\PlanArchiveController::class, 'index'])->name('index');
            Route::patch('{id}/restore', [\App\Http\Controllers\Admin\Archive\Plans\PlanArchiveController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [\App\Http\Controllers\Admin\Archive\Plans\PlanArchiveController::class, 'forceDelete'])->name('force-delete');
        });
        Route::prefix('coupons')->name('coupons.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\Archive\Coupons\CouponArchiveController::class, 'index'])->name('index');
            Route::patch('{id}/restore', [\App\Http\Controllers\Admin\Archive\Coupons\CouponArchiveController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [\App\Http\Controllers\Admin\Archive\Coupons\CouponArchiveController::class, 'forceDelete'])->name('force-delete');
        });
        Route::prefix('tenants')->name('tenants.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\Archive\Tenants\TenantArchiveController::class, 'index'])->name('index');
            Route::patch('{id}/restore', [\App\Http\Controllers\Admin\Archive\Tenants\TenantArchiveController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [\App\Http\Controllers\Admin\Archive\Tenants\TenantArchiveController::class, 'forceDelete'])->name('force-delete');
        });
        Route::prefix('bills')->name('bills.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\Archive\Bills\BillArchiveController::class, 'index'])->name('index');
            Route::patch('{id}/restore', [\App\Http\Controllers\Admin\Archive\Bills\BillArchiveController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [\App\Http\Controllers\Admin\Archive\Bills\BillArchiveController::class, 'forceDelete'])->name('force-delete');
        });
        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Admin\Archive\Users\AdminUserArchiveController::class, 'index'])->name('index');
            Route::patch('{id}/restore', [\App\Http\Controllers\Admin\Archive\Users\AdminUserArchiveController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [\App\Http\Controllers\Admin\Archive\Users\AdminUserArchiveController::class, 'forceDelete'])->name('force-delete');
        });
    });
});
