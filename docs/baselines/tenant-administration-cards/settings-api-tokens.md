Administration: Tenant Settings & API Tokens
Boundary: tenant
Scope / aggregate root: Tenant configuration, company brand customization, API access tokens
Routes + middleware + guard: routes/tenant.php (settings.*, api-tokens.*) + auth:tenant + settings permission
Controller: App\Http\Controllers\Tenants\Settings\SettingsController, App\Http\Controllers\Tenants\Settings\ApiTokenSettingsController
FormRequests: UpdateTenantSettingsRequest, StoreApiTokenRequest
Policy registration + abilities: Typed settings and token FormRequests plus route capabilities; token work is explicitly delegated across the Tenant/Central boundary.
Actions / Services / Gateways: UpdateTenantSettingsAction, CentralTenantApiTokenGateway
Models / Enums / Events / Jobs: Setting, PersonalAccessToken
DataTable / index / filters / buttons: ApiTokensDataTable for active token listings
Create UI + draft resource key + restore/purge evidence: Token generation modal and settings forms
Edit UI: resources/views/tenant/settings/index.blade.php
Review step: Not applicable
Show UI + child tables + attachments: Token revelation dialog
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: form-validation.js, copyable-code-wrapper
Files and storage lifecycle: tenant_public disk for logo and favicon customization
Tests: Tests\Unit\TenantSettingsArchitectureContractTest, Tests\Feature\TenantDashboardComprehensiveTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantSettingsArchitectureContractTest.php (16 passed, 72 assertions)
Not applicable items: Review step, archive
Remaining gaps: None
Status: Compliant
