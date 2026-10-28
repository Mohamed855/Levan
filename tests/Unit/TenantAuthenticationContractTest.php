<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenants\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Tenants\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorChallengeController;
use App\Http\Controllers\Tenants\Auth\Fortify\TenantTwoFactorSettingsController;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use App\Http\Controllers\Tenants\Auth\PasswordController;
use App\Http\Controllers\Tenants\Auth\PasswordResetLinkController;
use App\Http\Controllers\Tenants\Auth\RegisteredUserController;
use App\Http\Requests\Tenants\Auth\ConfirmTenantPasswordRequest;
use App\Http\Requests\Tenants\Auth\ConfirmTenantTwoFactorRequest;
use App\Http\Requests\Tenants\Auth\ResendPasswordResetOtpRequest;
use App\Http\Requests\Tenants\Auth\ResetTenantPasswordRequest;
use App\Http\Requests\Tenants\Auth\SendPasswordResetLinkRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\Auth\TenantTwoFactorChallengeRequest;
use App\Http\Requests\Tenants\Auth\UpdateTenantPasswordRequest;
use App\Http\Requests\Tenants\Auth\VerifyPasswordResetOtpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TenantAuthenticationContractTest extends TestCase
{
    public function test_authentication_mutations_use_their_dedicated_form_requests(): void
    {
        $contracts = [
            [PasswordResetLinkController::class, 'store', SendPasswordResetLinkRequest::class],
            [PasswordResetLinkController::class, 'verifyOtp', VerifyPasswordResetOtpRequest::class],
            [PasswordResetLinkController::class, 'resendOtp', ResendPasswordResetOtpRequest::class],
            [NewPasswordController::class, 'store', ResetTenantPasswordRequest::class],
            [NewPasswordController::class, 'updatePassword', ResetTenantPasswordRequest::class],
            [PasswordController::class, 'update', UpdateTenantPasswordRequest::class],
            [ConfirmablePasswordController::class, 'store', ConfirmTenantPasswordRequest::class],
            [TenantTwoFactorChallengeController::class, 'store', TenantTwoFactorChallengeRequest::class],
            [TenantTwoFactorSettingsController::class, 'confirm', ConfirmTenantTwoFactorRequest::class],
            [TenantTwoFactorSettingsController::class, 'enable', TenantAuthenticatedRequest::class],
            [TenantTwoFactorSettingsController::class, 'disable', TenantAuthenticatedRequest::class],
            [TenantTwoFactorSettingsController::class, 'recoveryCodes', TenantAuthenticatedRequest::class],
            [EmailVerificationNotificationController::class, 'store', TenantAuthenticatedRequest::class],
            [AuthenticatedSessionController::class, 'destroy', TenantAuthenticatedRequest::class],
        ];

        foreach ($contracts as [$controller, $method, $request]) {
            $parameter = (new ReflectionClass($controller))->getMethod($method)->getParameters()[0];

            $this->assertSame($request, $parameter->getType()?->getName());
        }
    }

    public function test_authentication_form_requests_define_authorization_and_rules(): void
    {
        foreach ([
            SendPasswordResetLinkRequest::class,
            VerifyPasswordResetOtpRequest::class,
            ResendPasswordResetOtpRequest::class,
            ResetTenantPasswordRequest::class,
            ConfirmTenantPasswordRequest::class,
            ConfirmTenantTwoFactorRequest::class,
            TenantTwoFactorChallengeRequest::class,
            TenantAuthenticatedRequest::class,
            UpdateTenantPasswordRequest::class,
        ] as $request) {
            $reflection = new ReflectionClass($request);

            $this->assertTrue($reflection->hasMethod('authorize'));
            $this->assertTrue($reflection->hasMethod('rules'));
        }
    }

    public function test_authentication_controllers_do_not_define_inline_request_rules(): void
    {
        foreach ([
            PasswordResetLinkController::class,
            NewPasswordController::class,
            ConfirmablePasswordController::class,
            TenantTwoFactorChallengeController::class,
            TenantTwoFactorSettingsController::class,
            PasswordController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertDoesNotMatchRegularExpression('/\\$request->validate\s*\(/', $content);
        }
    }

    public function test_authenticated_tenant_controllers_delegate_persistence_to_actions(): void
    {
        $controllers = [
            AuthenticatedSessionController::class,
            TenantTwoFactorChallengeController::class,
            TenantTwoFactorSettingsController::class,
            PasswordController::class,
            NewPasswordController::class,
            RegisteredUserController::class,
        ];

        foreach ($controllers as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertDoesNotMatchRegularExpression('/(?:->(?:update|save|forceFill)|User::create)\s*\(/', $content);
        }
    }

    public function test_public_auth_forms_use_the_shared_validation_contract(): void
    {
        foreach ([
            'login.blade.php',
            'forgot-password.blade.php',
            'reset-password.blade.php',
            'two-factor-challenge.blade.php',
            'register.blade.php',
        ] as $view) {
            $content = file_get_contents(dirname(__DIR__, 2).'/resources/views/tenant/auth/'.$view);

            $this->assertStringContainsString('data-form-validate', $content);
            $this->assertStringContainsString("tenant_route_name('form-validation')", $content);
            $this->assertStringNotContainsString('autofocus', $content);
        }
    }

    public function test_auth_forms_do_not_validate_on_page_load(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('function isAuthForm(form)', $script);
        $this->assertStringContainsString('const isAuth = isAuthForm(form);', $script);
        $this->assertStringContainsString('if (!isAuth) {', $script);
    }

    public function test_two_factor_setup_uses_the_shared_validation_contract(): void
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/resources/views/tenant/auth/two-factor-settings.blade.php');

        $this->assertStringContainsString('data-form-validate', $content);
        $this->assertStringContainsString("tenant_route_name('wizard-drafts.validate-step')", $content);
        $this->assertStringContainsString('SharedFormValidation', file_get_contents(dirname(__DIR__, 2).'/resources/assets/js/shared/form-validation.js'));
    }
}
