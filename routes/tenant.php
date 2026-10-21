<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\Auctions\AuctionController;
use App\Http\Controllers\Tenant\Assignments\AssignmentController;
use App\Http\Controllers\Tenant\Properties\PropertyController;
use App\Http\Controllers\Tenant\Dashboard\DashboardController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenant')->name('tenant.')->middleware('auth:tenant')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('wizard-drafts')->name('wizard-drafts.')->group(function (): void {
        Route::get('/', [WizardDraftController::class, 'show'])->name('index');
        Route::post('/', [WizardDraftController::class, 'store'])->name('store');
        Route::delete('/', [WizardDraftController::class, 'destroy'])->name('destroy');
    });
    Route::get('auctions/{auction}/preview/{type}', [AuctionController::class, 'preview'])->name('auctions.preview');
    Route::resource('auctions', AuctionController::class)->except(['destroy']);
    Route::resource('assignments', AssignmentController::class)->except(['destroy']);
    Route::patch('assignments/{assignment}/toggle-status', [AssignmentController::class, 'toggleStatus'])->name('assignments.toggle-status');
    Route::resource('properties', PropertyController::class)->except(['destroy']);
    Route::patch('properties/{property}/toggle-status', [PropertyController::class, 'toggleStatus'])->name('properties.toggle-status');
});
