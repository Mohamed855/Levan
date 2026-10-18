Administration: Content Management: Portfolios
Boundary: central
Scope / aggregate root: Central marketing showcase items, portfolio project items, project media files
Routes + middleware + guard: routes/admin/admin.php (admin.content_management.portfolio.*) + auth:admin + permission:clients.*
Controller: App\Http\Controllers\Admin\ContentManagement\Portfolio\PortfolioController
FormRequests: App\Http\Requests\Admin\ContentManagement\StorePortfolioRequest, App\Http\Requests\Admin\ContentManagement\UpdatePortfolioRequest
Policy registration + abilities: App\Policies\Admin\ContentManagement\ContentPortfolioPolicy registered in AuthServiceProvider for ContentPortfolio::class
Actions / Services / Gateways: App\Actions\Admin\ContentManagement\ManagePortfolioAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\ContentManagement\Portfolio
DataTable / index / filters / buttons: App\DataTables\Admin\ContentManagement\Portfolio\PortfolioDataTable (status switch, export buttons, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/content_management/portfolio/create.blade.php + draft support
Edit UI: resources/views/admin/content_management/portfolio/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/content_management/portfolio/details.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Portfolio project media files stored on public disk with automatic cleanup
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_content_management_crud_lifecycle"
Not applicable items: Review step, archive
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
