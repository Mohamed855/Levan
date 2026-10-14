Administration: Tenant Centralized Archive
Boundary: tenant
Scope / aggregate root: Soft-deleted tenant entities restoration and permanent purge (Users, Companies, Contracts, Portfolios, Customers, Collections, Assignments, Properties, Auctions)
Routes + middleware + guard: routes/tenant/tenant.php (archives.*) + auth:tenant + permission per entity
Controller: App\Http\Controllers\Tenants\Archive\UserArchiveController, CompanyArchiveController, ContractArchiveController, PortfolioArchiveController, CustomerArchiveController, AssignmentArchiveController, PropertyArchiveController, AuctionArchiveController
FormRequests: TenantArchiveRestoreRequest, TenantArchiveForceDeleteRequest
Policy registration + abilities: Entity policies restore and forceDelete abilities
Actions / Services / Gateways: RestoreEntityAction, ForceDeleteEntityAction per aggregate
Models / Enums / Events / Jobs: User, Company, Contract, Portfolio, Customer, Assignment, Property, Auction
DataTable / index / filters / buttons: Dedicated Archive DataTables without header banners, with Excel/Copy and restore/delete actions
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Archive views strictly contain error alerts, filters, and table card
Shared components and JavaScript contracts: table-actions.js, mass-delete.js
Files and storage lifecycle: Complete file cleanup cascade from tenant disk upon force delete
Tests: Tests\Unit\TenantArchivePolicyContractTest, Tests\Feature\TenantAssetModulesFeatureTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantArchivePolicyContractTest.php (3 passed, 44 assertions)
Not applicable items: Create UI, edit UI, drafts, review step, show UI
Remaining gaps: None
Status: Compliant
