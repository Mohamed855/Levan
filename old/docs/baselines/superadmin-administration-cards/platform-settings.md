Administration: Platform Global Settings
Boundary: central
Scope / aggregate root: Central system settings, brand logo/favicon, SMTP mail configuration, payment gateways credentials, caching
Routes + middleware + guard: routes/admin.php (admin.settings.*) + auth:admin + permission:system.settings
Controller: App\Http\Controllers\Admin\Settings\SettingsController
FormRequests: App\Http\Requests\Admin\Settings\AdminMainSettingsRequest
Policy registration + abilities: Not applicable (system settings aggregate view guarded by permission:system.settings middleware)
Actions / Services / Gateways: App\Actions\Admin\Settings\UpdatePlatformSettingsAction
Models / Enums / Events / Jobs: App\Models\Admin\Settings\Settings
DataTable / index / filters / buttons: Not applicable (tabbed settings interface)
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: resources/views/admin/settings/settings.blade.php
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: form-validation.js
Files and storage lifecycle: Platform brand logo and favicon stored on public disk
Tests: Tests\Unit\TenantSettingsArchitectureContractTest, Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantSettingsArchitectureContractTest.php --filter="test_settings_controllers_delegate_mutations_to_actions|test_settings_actions_own_transactions_cache_and_file_lifecycle"
Not applicable items: Policy, DataTable, create UI, drafts, review step, show UI, archive
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
