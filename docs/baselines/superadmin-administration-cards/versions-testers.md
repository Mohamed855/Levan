Administration: Platform Versions & Beta Testers
Boundary: central
Scope / aggregate root: Software release notes, changelog publishing, version tester invitations and user feedback
Routes + middleware + guard: routes/admin/admin.php (admin.versions.*, admin.version_testers.*) + auth:admin + permission:versions.*, version_testers.*
Controller: App\Http\Controllers\Admin\Versions\VersionController, App\Http\Controllers\Admin\VersionTester\VersionTesterController
FormRequests: App\Http\Requests\Admin\Versions\VersionRequest, App\Http\Requests\Admin\VersionTesters\VersionTesterRequest
Policy registration + abilities: App\Policies\Admin\Versions\VersionPolicy, App\Policies\Admin\Versions\VersionTesterPolicy registered in AuthServiceProvider
Actions / Services / Gateways: App\Services\Admin\Versions\VersionService, App\Services\Admin\VersionTesters\VersionTestersService
Models / Enums / Events / Jobs: App\Models\Admin\Version\Version, App\Models\Admin\Version\VersionTester
DataTable / index / filters / buttons: App\DataTables\Admin\Versions\VersionDataTable, App\DataTables\Admin\VersionTester\VersionTesterDataTable (status switch, export buttons, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/versions/create.blade.php, resources/views/admin/version_testers/create.blade.php + draft support
Edit UI: resources/views/admin/versions/edit.blade.php, resources/views/admin/version_testers/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/versions/show.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Release archive binaries and assets stored on secure disk
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_versions_and_testers_crud_lifecycle"
Not applicable items: Review step, archive
Remaining gaps: None
Status: Compliant
