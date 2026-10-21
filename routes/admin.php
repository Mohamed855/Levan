<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuctionTemplates\AuctionTemplateController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function (): void {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
    });
    Route::resource('auction-templates', AuctionTemplateController::class)->except(['destroy'])->parameters(['auction-templates' => 'auctionTemplate']);
    Route::patch('auction-templates/{auctionTemplate}/toggle-status', [AuctionTemplateController::class, 'toggleStatus'])->name('auction-templates.toggle-status');
    Route::post('auction-templates/{auctionTemplate}/versions/{version}/publish', [AuctionTemplateController::class, 'publish'])->name('auction-templates.versions.publish');
    Route::resource('plans', PlanController::class)->except(['destroy']);
    Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
});
