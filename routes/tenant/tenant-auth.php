<?php

use App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenants\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Tenants\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Tenants\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorChallengeController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorSettingsController;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use App\Http\Controllers\Tenants\Auth\PasswordController;
use App\Http\Controllers\Tenants\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenants\Auth\RegisteredUserController;
use App\Http\Controllers\Tenants\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:tenant')->group(function () {
    Route::name('register')->get('register', [RegisteredUserController::class, 'create']);
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::name('login')->get('login', [AuthenticatedSessionController::class, 'create']);
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::name('password.request')->get('forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::name('password.email')->post('forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::name('password.reset')->get('reset-password/{token}', [NewPasswordController::class, 'create']);
    Route::name('password.store')->post('reset-password', [NewPasswordController::class, 'store']);

    Route::get('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'create'])->name('tenants.two-factor.login');
    Route::post('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'store'])->name('tenants.two-factor.store')->middleware('throttle:5,1');
    Route::get('two-factor-challenge/cancel', [TenantTwoFactorChallengeController::class, 'cancel'])->name('tenants.two-factor.cancel');
});

Route::middleware('auth:tenant')->group(function () {
    Route::name('verification.notice')->get('verify-email', EmailVerificationPromptController::class);
    Route::name('verification.verify')->get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1']);
    Route::name('verification.send')->post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1');
    Route::name('password.confirm')->get('confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::name('password.update')->put('password', [PasswordController::class, 'update']);
    Route::name('logout')->post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::name('tenants.two-factor.')->group(function () {
        Route::get('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'show'])->name('index');
        Route::post('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'enable'])->name('enable');
        Route::delete('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'disable'])->name('disable');
        Route::post('confirmed-two-factor-authentication', [TenantTwoFactorSettingsController::class, 'confirm'])->name('confirm');
        Route::get('two-factor-qr-code', [TenantTwoFactorSettingsController::class, 'qrCode'])->name('qr-code');
        Route::post('two-factor-recovery-codes', [TenantTwoFactorSettingsController::class, 'recoveryCodes'])->name('recovery-codes');
    });
});
