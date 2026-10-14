Administration: Tenant Business Center: Contracts
Boundary: tenant
Scope / aggregate root: Corporate client contracts, agreement terms, milestones, contract files
Routes + middleware + guard: routes/tenant/tenant.php (contracts.*) + auth:tenant + contracts permission
Controller: App\Http\Controllers\Tenants\BusinessCenter\Contract\ContractController, App\Http\Controllers\Tenants\Archive\Contract\ContractArchiveController
FormRequests: StoreContractRequest, UpdateContractRequest, UpdateResourceStatusRequest
Policy registration + abilities: ContractPolicy registered in AuthServiceProvider
Actions / Services / Gateways: ContractService; archive controller record actions
Models / Enums / Events / Jobs: Contract, ContractAttachment
DataTable / index / filters / buttons: ContractsDataTable (5 lifecycle stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/business_center/contracts/create.blade.php + draft support
Edit UI: resources/views/tenants/business_center/contracts/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/tenants/business_center/contracts/show.blade.php (two-column: contract terms + attachments & portfolios)
Archive controller / DataTable / views: ContractArchiveController, ContractArchiveDataTable, resources/views/tenants/archive/contracts/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Contract PDF and agreement files stored on tenant disk with signed download authorization
Tests: Tests\Feature\TenantCrudFeatureTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantCrudFeatureTest.php --filter="test_contracts_crud_and_archive_lifecycle" (1 passed, 27 assertions)
Not applicable items: Review step
Remaining gaps: None
Status: Compliant
