Administration: Tenant Users, Teams, and Roles
Boundary: tenant
Scope / aggregate root: Tenant User, Team, and Spatie Role & Permission management
Routes + middleware + guard: routes/tenant.php (users.*, teams.*, roles.*) + auth:tenant + Spatie permissions
Controller: App\Http\Controllers\Tenants\UserMangment\UserController, TeamController, RoleController
FormRequests: StoreUserRequest, UpdateUserRequest, TeamStoreRequest, TeamUpdateRequest, RoleStoreRequest, RoleUpdateRequest, UpdateResourceStatusRequest
Policy registration + abilities: UserPolicy, TeamPolicy, RolePolicy registered in AuthServiceProvider
Actions / Services / Gateways: UserService, TeamService, Spatie Role actions
Models / Enums / Events / Jobs: User, Team, Role, Permission
DataTable / index / filters / buttons: UsersDataTable, TeamsDataTable, RolesDataTable (status switch, export buttons, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/tenant/user_mangment/users/create.blade.php + draft support
Edit UI: resources/views/tenant/user_mangment/users/edit.blade.php
Review step: Not applicable (single-card and standard tabbed forms)
Show UI + child tables + attachments: resources/views/tenant/user_mangment/users/show.blade.php (asymmetric layout with team/role info)
Archive controller / DataTable / views: UserArchiveController, UserArchiveDataTable, resources/views/tenant/archive/users/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, mass-delete.js
Files and storage lifecycle: tenant_public disk for user avatars, cleanup on force delete
Tests: Tests\Feature\TenantUserManagementValidationFeatureTest, Tests\Feature\TenantCrudFeatureTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantUserManagementValidationFeatureTest.php (6 passed, 40 assertions)
Not applicable items: Review step (forms are single card/tabbed)
Remaining gaps: None
Status: Compliant
