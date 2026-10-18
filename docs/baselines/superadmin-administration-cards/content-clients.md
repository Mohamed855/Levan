Administration: Content Management: Clients
Boundary: central
Scope / aggregate root: Central marketing website client logos, testimonials, display ordering
Routes + middleware + guard: routes/admin/admin.php (admin.content_management.client.*) + auth:admin + permission:clients.*
Controller: App\Http\Controllers\Admin\ContentManagement\Client\ClientController
FormRequests: App\Http\Requests\Admin\ContentManagement\StoreClientRequest, App\Http\Requests\Admin\ContentManagement\UpdateClientRequest
Policy registration + abilities: App\Policies\Admin\ContentManagement\ClientPolicy registered in AuthServiceProvider for Client::class
Actions / Services / Gateways: App\Actions\Admin\ContentManagement\ManageClientAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\ContentManagement\Client
DataTable / index / filters / buttons: App\DataTables\Admin\ContentManagement\Client\ClientDataTable (status switch, export buttons, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/content_management/client/create.blade.php + draft support
Edit UI: resources/views/admin/content_management/client/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/content_management/client/details.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Client logo images stored on public disk with atomic cleanup on replacement
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_content_management_crud_lifecycle"
Not applicable items: Review step, archive
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
