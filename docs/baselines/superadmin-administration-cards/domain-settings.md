Administration: Central Domain Settings & Control TLDs
Boundary: central
Scope / aggregate root: Platform allowed domain names, central domains for tenant subdomains, DNS server configuration
Routes + middleware + guard: routes/admin/admin.php (admin.settings.domains.*) + auth:admin + permission:domain_extension_prices.settings
Controller: App\Http\Controllers\Admin\Settings\Domain\SettingsDomainController
FormRequests: App\Http\Requests\Admin\Settings\Domain\StoreControlDomainRequest, App\Http\Requests\Admin\Settings\Domain\UpdateControlDomainRequest
Policy registration + abilities: App\Policies\Admin\Settings\ControlDomainPolicy registered in AuthServiceProvider for ControlDomain::class
Actions / Services / Gateways: App\Actions\Admin\Settings\ManageControlDomainAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\ControlDomain\ControlDomain
DataTable / index / filters / buttons: App\DataTables\Admin\Setting\SettingDomainDataTable (status switch, copy/excel/mass-delete, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/settings/domains/create.blade.php + draft support
Edit UI: resources/views/admin/settings/domains/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_settings_banks_and_domains_crud_lifecycle"
Not applicable items: Review step, show UI, archive, files
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
