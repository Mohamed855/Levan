Administration: Central Mass Delete
Boundary: central
Scope / aggregate root: Multi-record deletion across authorized central models registered in AdminMassDeleteController::ALLOWED_MODELS
Routes + middleware + guard: routes/admin/admin.php (admin.mass-delete) + auth:admin
Controller: App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController
FormRequests: App\Http\Requests\Admin\DeleteBulk\MassDeleteRequest
Policy registration + abilities: Per-record policy delete check for every loaded record instance in selection payload
Actions / Services / Gateways: App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: All central soft-deletable models mapped in AdminMassDeleteController::ALLOWED_MODELS
DataTable / index / filters / buttons: Shared custom-checkbox and select-all handlers in all central DataTables
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: mass-delete.js, confirmDeleteSelectedmss handler
Files and storage lifecycle: Cascade soft deletion / file handling via model observers or actions
Tests: Tests\Unit\MassDeletePolicyContractTest, Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/MassDeletePolicyContractTest.php
Not applicable items: Create UI, edit UI, drafts, review step, show UI, archive
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
