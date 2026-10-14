Administration: Central Bank Accounts Settings
Boundary: central
Scope / aggregate root: Central company bank accounts for subscription transfer deposits, IBAN, bank logos
Routes + middleware + guard: routes/admin/admin.php (admin.settings.banks.*) + auth:admin + permission:bank_settings.*
Controller: App\Http\Controllers\Admin\Settings\Bank\SettingsBankController
FormRequests: App\Http\Requests\Admin\Settings\Bank\StoreBankRequest, App\Http\Requests\Admin\Settings\Bank\UpdateBankRequest
Policy registration + abilities: App\Policies\Admin\Settings\BankPolicy registered in AuthServiceProvider for Bank::class
Actions / Services / Gateways: App\Actions\Admin\Settings\ManageBankAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\Bank\Bank
DataTable / index / filters / buttons: App\DataTables\Admin\Setting\Bank\BankDataTable (status switch, copy/excel/mass-delete, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/settings/banks/create.blade.php + draft support
Edit UI: resources/views/admin/settings/banks/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Bank logo icons stored on public disk with automatic removal on deletion
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_settings_banks_and_domains_crud_lifecycle"
Not applicable items: Review step, show UI, archive
Remaining gaps: None
Status: Compliant
