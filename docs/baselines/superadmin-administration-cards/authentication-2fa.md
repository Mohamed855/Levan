Administration: SuperAdmin Authentication & Two-Factor Challenge
Boundary: central
Scope / aggregate root: Admin session lifecycle, brute-force rate-limiting, TOTP 2FA secret generation, QR codes, recovery codes, authentication logging
Routes + middleware + guard: routes/admin/admin-auth.php (admin.login, admin.logout, admin.two-factor.*) + guest:admin / auth:admin
Controller: App\Http\Controllers\Admin\Auth\AdminAuthController, App\Http\Controllers\Admin\Auth\AdminTwoFactorChallengeController, App\Http\Controllers\Admin\Auth\AdminTwoFactorSettingsController
FormRequests: App\Http\Requests\Admin\Auth\AdminLoginRequest, App\Http\Requests\Admin\Auth\AdminTwoFactorChallengeRequest, App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest, App\Http\Requests\Admin\Auth\ConfirmAdminTwoFactorRequest
Policy registration + abilities: Not applicable (guest authentication and admin self-service 2FA configuration)
Actions / Services / Gateways: App\Actions\Auth\PersistAuthenticationStateAction, App\Actions\Auth\ResolveTwoFactorLogin, Laravel\Fortify\Actions\EnableTwoFactorAuthentication, Laravel\Fortify\Actions\DisableTwoFactorAuthentication, Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication
Models / Enums / Events / Jobs: App\Models\Admin\AdminMangment\Users\AdminUser
DataTable / index / filters / buttons: Not applicable (authentication views)
Create UI + draft resource key + restore/purge evidence: Not applicable (auth forms)
Edit UI: resources/views/admin/auth/two-factor-settings.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/auth/login.blade.php, resources/views/admin/auth/two-factor-challenge.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: form-validation.js, password-toggle.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminAuthenticationValidationFeatureTest, Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminAuthenticationValidationFeatureTest.php tests/Unit/SuperAdminContractTest.php
Not applicable items: Policy, DataTable, drafts, review step, archive, files
Remaining gaps: None
Status: Compliant
