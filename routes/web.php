<?php

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Site\LandingPageController;
use App\Http\Controllers\Site\SubscriptionController;
use App\Http\Controllers\Site\TrialController;
use Illuminate\Support\Facades\Route;

Route::name('site.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('privacy-policy', [LandingPageController::class, 'privacyPolicy'])->name('privacy.policy');
    Route::post('contact', [LandingPageController::class, 'contactRequest'])->defaults('shared_form_validation', true)->name('contact.store');

    Route::prefix('trial')->group(function () {
        Route::get('/', [TrialController::class, 'index'])->name('trial');
        Route::post('/', [TrialController::class, 'demoRequest'])->middleware('throttle:6,1')->defaults('shared_form_validation', true)->name('trial.store');
        Route::get('edit', [TrialController::class, 'edit'])->name('trial.edit');
        Route::post('verify-email', [TrialController::class, 'verify'])->middleware('throttle:10,1')->name('trial.verify');
        Route::post('resend-otp', [TrialController::class, 'resendOtp'])->middleware('throttle:6,1')->name('trial.resend');
    });

    Route::name('plans.')->group(function () {
        Route::prefix('checkout/{plan}')->group(function () {
            Route::get('details', [SubscriptionController::class, 'checkout'])->name('checkout');
            Route::get('edit', [SubscriptionController::class, 'editCheckout'])->name('checkout.edit');
            Route::post('/', [SubscriptionController::class, 'processCheckout'])->defaults('shared_form_validation', true)->name('checkout.store');
            Route::post('verify-email', [SubscriptionController::class, 'verifyOtp'])->name('checkout.verify.store');
            Route::post('resend-otp', [SubscriptionController::class, 'resendOtp'])->middleware('throttle:6,1')->name('checkout.resend');
            Route::post('payment', [SubscriptionController::class, 'completeCheckout'])->defaults('shared_form_validation', true)->name('checkout.payment');
        });
        Route::get('checkout/callback', [SubscriptionController::class, 'handlePaymentCallback'])->name('checkout.callback');
        Route::get('checkout/retry/{billNumber}', [SubscriptionController::class, 'retryCheckout'])->middleware('signed')->name('checkout.retry');
    });

    Route::match(['post', 'put', 'patch'], 'form-validation', [WizardDraftController::class, 'validateStep'])->defaults('shared_form_validation_actions', [
        LandingPageController::class.'@contactRequest',
        SubscriptionController::class.'@processCheckout',
        SubscriptionController::class.'@completeCheckout',
        TrialController::class.'@demoRequest',
    ])->name('form-validation');
});
