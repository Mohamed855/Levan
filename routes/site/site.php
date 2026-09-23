<?php

use App\Http\Controllers\Site\LandingPageController;
use App\Http\Controllers\Site\SubscriptionController;
use App\Http\Controllers\Site\TrialController;
use Illuminate\Support\Facades\Route;

Route::name('site.')->group(function () {

    // Landing Page
    // Route::view('/', 'site.coming-soon')->name('index');
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('privacy-policy', [LandingPageController::class, 'privacyPolicy'])->name('privacy.policy');
    Route::post('contact', [LandingPageController::class, 'contactRequest'])->name('contact.store');  // contact request


    Route::prefix('')->name('plans.')->group(function () {
        Route::get('checkout/callback', [SubscriptionController::class, 'handlePaymentCallback'])->name('checkout.callback');
        Route::post('checkout/{plan}', [SubscriptionController::class, 'processCheckout'])->name('checkout.store');  // الدفع
        Route::get('checkout/{plan}/details', [SubscriptionController::class, 'checkout'])->name('checkout'); // عرض التفاصيل
    });

    // Trial Routes
    Route::get('/trial', [TrialController::class, 'index'])->name('trial');
    Route::post('/trial', [TrialController::class, 'demoRequest'])->name('trial.store');
});
