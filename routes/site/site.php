<?php

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Site\LandingPageController;
use App\Http\Controllers\Site\SubscriptionController;
use App\Http\Controllers\Site\TrialController;
use Illuminate\Support\Facades\Route;

Route::name('site.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('privacy-policy', [LandingPageController::class, 'privacyPolicy'])->name('privacy.policy');
    Route::post('contact', [LandingPageController::class, 'contactRequest'])
        ->defaults('shared_form_validation', true)
        ->name('contact.store');
    Route::post('form-validation', [WizardDraftController::class, 'validateStep'])
        ->defaults('shared_form_validation_actions', [
            LandingPageController::class.'@contactRequest',
            SubscriptionController::class.'@processCheckout',
            TrialController::class.'@demoRequest',
        ])
        ->name('form-validation');

    Route::prefix('')->name('plans.')->group(function () {
        Route::get('checkout/callback', [SubscriptionController::class, 'handlePaymentCallback'])->name('checkout.callback');
        Route::post('checkout/{plan}', [SubscriptionController::class, 'processCheckout'])
            ->defaults('shared_form_validation', true)
            ->name('checkout.store');  // الدفع
        Route::post('checkout/{plan}/verify-email', [SubscriptionController::class, 'verifyOtp'])->name('checkout.verify.store');
        Route::get('checkout/{plan}/edit', [SubscriptionController::class, 'editCheckout'])->name('checkout.edit');
        Route::post('checkout/{plan}/payment', [SubscriptionController::class, 'completeCheckout'])->name('checkout.payment');
        Route::get('checkout/{plan}/details', [SubscriptionController::class, 'checkout'])->name('checkout'); // عرض التفاصيل
    });

    // Trial Routes
    Route::get('/trial', [TrialController::class, 'index'])->name('trial');
    Route::post('/trial', [TrialController::class, 'demoRequest'])
        ->defaults('shared_form_validation', true)
        ->name('trial.store');
});
