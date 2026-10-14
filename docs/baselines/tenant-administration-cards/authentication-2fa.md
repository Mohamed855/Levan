Administration: Tenant Authentication & 2FA
Boundary: tenant
Scope / aggregate root: Tenant user session, password management, email verification, two-factor authentication challenge and setup
Routes + middleware + guard: routes/tenant/tenant.php (login, logout, register, password.*, verification.*, tenants.two-factor.*) + guest:tenant / auth:tenant
Controller: App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController, RegisteredUserController, PasswordResetLinkController, NewPasswordController, ConfirmablePasswordController, VerifyEmailController, Fortify\TenantTwoFactorAuthenticatedSessionController, Fortify\TenantTwoFactorQrCodeController, Fortify\TenantTwoFactorSecretKeyController, Fortify\TenantRecoveryCodeController
FormRequests: LoginRequest, TenantProfileUploadRequest, TenantTwoFactorChallengeRequest, Fortify\TenantTwoFactorConfirmedRequest
Policy registration + abilities: UserPolicy (profile/account), Session/Auth flows bypass policy via guest guard
Actions / Services / Gateways: RegisterTenantUserAction, ResetTenantPasswordAction, Fortify 2FA actions
Models / Enums / Events / Jobs: User, UserSession
DataTable / index / filters / buttons: Not applicable (standard authentication views)
Create UI + draft resource key + restore/purge evidence: Not applicable (auth views)
Edit UI: Profile modal and security settings view
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: form-validation.js, password-toggle.js
Files and storage lifecycle: tenant_public disk for profile photo uploads, cleanup on replace/delete
Tests: Tests\Feature\TenantAuthenticationValidationFeatureTest, Tests\Unit\TenantAuthenticationContractTest, Tests\Feature\TenantProfileUploadFeatureTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantAuthenticationValidationFeatureTest.php tests/Feature/TenantProfileUploadFeatureTest.php tests/Unit/TenantAuthenticationContractTest.php (11 passed, 44 assertions)
Not applicable items: DataTable, drafts, review step, show UI, archive (standard public/session auth flows)
Remaining gaps: None
Status: Compliant
