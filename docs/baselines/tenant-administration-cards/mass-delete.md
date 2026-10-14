Administration: Tenant Mass Delete
Boundary: tenant
Scope / aggregate root: Multi-record deletion across authorized tenant models registered in config/models.php
Routes + middleware + guard: routes/tenant/tenant.php (mass-delete) + auth:tenant
Controller: App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController
FormRequests: MassDeleteRequest
Policy registration + abilities: Per-record policy delete check for every ID in payload
Actions / Services / Gateways: DeleteTenantRecordsAction
Models / Enums / Events / Jobs: All soft-deletable models mapped in config/models.php
DataTable / index / filters / buttons: Shared custom-checkbox and select-all handlers in all index DataTables
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: mass-delete.js, confirmDeleteSelectedmss handler
Files and storage lifecycle: Cascade soft deletion / file handling via model observers or actions
Tests: Tests\Unit\MassDeletePolicyContractTest, Tests\Feature\TenantAssetModulesFeatureTest
Verification commands and passing evidence: php artisan test tests/Unit/MassDeletePolicyContractTest.php (2 passed, 33 assertions)
Not applicable items: Create UI, edit UI, drafts, review step, show UI, archive
Remaining gaps: None
Status: Compliant
