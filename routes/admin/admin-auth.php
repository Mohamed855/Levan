<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorSettingsController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest:admin')->group(function () {
        Route::match(['post', 'put', 'patch'], 'form-validation', [WizardDraftController::class, 'validateStep'])
            ->defaults('shared_form_validation_actions', [
                AdminAuthController::class . '@login',
            ])
            ->name('form-validation');

        Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->defaults('shared_form_validation', true);

        Route::get('two-factor-challenge', [AdminTwoFactorChallengeController::class, 'create'])->name('two-factor.login');
        Route::post('two-factor-challenge', [AdminTwoFactorChallengeController::class, 'store'])->name('two-factor.store')->middleware('throttle:5,1');
        Route::get('two-factor-challenge/cancel', [AdminTwoFactorChallengeController::class, 'cancel'])->name('two-factor.cancel');
    });

    Route::middleware(['auth:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::name('two-factor.')->group(function () {
            Route::get('two-factor-authentication', [AdminTwoFactorSettingsController::class, 'show'])->name('index');
            Route::post('two-factor-authentication', [AdminTwoFactorSettingsController::class, 'enable'])->name('enable');
            Route::delete('two-factor-authentication', [AdminTwoFactorSettingsController::class, 'disable'])->name('disable');
            Route::post('confirmed-two-factor-authentication', [AdminTwoFactorSettingsController::class, 'confirm'])->name('confirm');
            Route::get('two-factor-qr-code', [AdminTwoFactorSettingsController::class, 'qrCode'])->name('qr-code');
            Route::post('two-factor-recovery-codes', [AdminTwoFactorSettingsController::class, 'recoveryCodes'])->name('recovery-codes');
        });
    });
});
