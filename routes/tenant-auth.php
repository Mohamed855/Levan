<?php

declare(strict_types=1);

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
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
use App\Http\Middleware\EnsureInitialPasswordChanged;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication: Password Reset & Change
|--------------------------------------------------------------------------
*/
Route::middleware('guest:tenant')->group(function () {
    Route::match(['post', 'put', 'patch'], 'form-validation', [WizardDraftController::class, 'validateStep'])
        ->defaults('shared_form_validation_actions', [
            AuthenticatedSessionController::class.'@store',
            RegisteredUserController::class.'@store',
            PasswordResetLinkController::class.'@store',
            PasswordResetLinkController::class.'@verifyOtp',
            NewPasswordController::class.'@store',
            TenantTwoFactorChallengeController::class.'@store',
        ])
        ->name('form-validation');

    Route::name('register')->get('register', [RegisteredUserController::class, 'create']);
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::name('login')->get('login', [AuthenticatedSessionController::class, 'create']);
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::name('password.request')->get('forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::name('password.email')->post('forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::name('password.otp.verify')->post('forgot-password/verify', [PasswordResetLinkController::class, 'verifyOtp'])
        ->defaults('shared_form_validation', true);
    Route::name('password.otp.resend')->post('forgot-password/resend-otp', [PasswordResetLinkController::class, 'resendOtp'])->middleware('throttle:6,1');
    Route::name('password.otp.edit')->get('forgot-password/edit', [PasswordResetLinkController::class, 'editEmail']);
    Route::name('password.reset')->get('reset-password', [NewPasswordController::class, 'create']);
    Route::name('password.store')->post('reset-password', [NewPasswordController::class, 'store']);

    Route::get('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'create'])->name('tenants.two-factor.login');
    Route::post('two-factor-challenge', [TenantTwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:5,1')
        ->defaults('shared_form_validation', true)
        ->name('tenants.two-factor.store');
    Route::get('two-factor-challenge/cancel', [TenantTwoFactorChallengeController::class, 'cancel'])->name('tenants.two-factor.cancel');
});

Route::middleware(['auth:tenant', EnsureInitialPasswordChanged::class.':tenant'])->group(function () {
    Route::post('confirm-password/validate', [WizardDraftController::class, 'validateStep'])
        ->defaults('shared_form_validation_actions', [ConfirmablePasswordController::class.'@store'])
        ->name('password.confirm.validate');
    Route::name('verification.notice')->get('verify-email', EmailVerificationPromptController::class);
    Route::name('verification.verify')->get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1']);
    Route::name('verification.send')->post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1');
    Route::name('password.confirm')->get('confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::name('password.update')->put('password', [PasswordController::class, 'update']);
    Route::name('logout')->post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::name('tenants.two-factor.')->group(function () {
        Route::post('two-factor-authentication/validate', [WizardDraftController::class, 'validateStep'])
            ->defaults('shared_form_validation_actions', [TenantTwoFactorSettingsController::class.'@confirm'])
            ->name('validate');
        Route::get('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'show'])->name('index');
        Route::post('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'enable'])->name('enable');
        Route::delete('two-factor-authentication', [TenantTwoFactorSettingsController::class, 'disable'])->name('disable');
        Route::post('confirmed-two-factor-authentication', [TenantTwoFactorSettingsController::class, 'confirm'])
            ->defaults('shared_form_validation', true)
            ->name('confirm');
        Route::get('two-factor-qr-code', [TenantTwoFactorSettingsController::class, 'qrCode'])->name('qr-code');
        Route::post('two-factor-recovery-codes', [TenantTwoFactorSettingsController::class, 'recoveryCodes'])->name('recovery-codes');
    });
});
