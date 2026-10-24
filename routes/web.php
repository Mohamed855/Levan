<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CouponQuoteController;
use App\Http\Controllers\Web\FormValidationController;
use App\Http\Controllers\Web\LandingPageController;
use App\Http\Controllers\Web\TrialController;
use Illuminate\Support\Facades\Route;

Route::name('site.')->group(function (): void {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('privacy-policy', [LandingPageController::class, 'privacyPolicy'])->name('privacy.policy');
    Route::post('contact', [LandingPageController::class, 'storeContact'])->middleware('throttle:10,1')->name('contact.store');

    Route::post('form-validation', FormValidationController::class)->name('form-validation');

    Route::prefix('checkout')->name('plans.checkout.')->group(function (): void {
        Route::post('{plan}/coupon-quote', CouponQuoteController::class)->middleware('throttle:20,1')->name('coupon-quote');
        Route::get('callback', [CheckoutController::class, 'callback'])->name('callback');
        Route::get('retry/{billNumber}', [CheckoutController::class, 'retry'])->middleware('signed')->name('retry');
        Route::post('{plan}', [CheckoutController::class, 'store'])->name('store');
        Route::post('{plan}/verify-email', [CheckoutController::class, 'verifyEmail'])->name('verify.store');
        Route::get('{plan}/edit', [CheckoutController::class, 'edit'])->name('edit');
        Route::post('{plan}/payment', [CheckoutController::class, 'completePayment'])->name('payment');
    });

    Route::get('checkout/{plan}/details', [CheckoutController::class, 'show'])->name('plans.checkout');

    Route::get('trial', [TrialController::class, 'index'])->name('trial');
    Route::post('trial', [TrialController::class, 'store'])->middleware('throttle:10,1')->name('trial.store');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
        Route::get('two-factor', [AdminAuthenticatedSessionController::class, 'twoFactorCreate'])->name('two-factor.create');
        Route::post('two-factor', [AdminAuthenticatedSessionController::class, 'twoFactorStore'])->middleware('throttle:5,1')->name('two-factor.store');
    });

    Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->middleware('auth:admin')->name('logout');
});

require __DIR__.'/admin.php';

