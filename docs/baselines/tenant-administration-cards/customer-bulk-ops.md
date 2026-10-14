Administration: Tenant Customer Bulk Operations
Boundary: tenant
Scope / aggregate root: Customer Excel import/export, bulk attribute updates, agent portfolio distribution, customer update requests, follow-up cycles
Routes + middleware + guard: routes/tenant/tenant.php (customers.{import,export,bulk-update,distribution,update-requests,follow}.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerImportController, CustomerExportController, CustomerBulkUpdateController, CustomerDistributionController, CustomerUpdateRequestController, CustomerFollowController
FormRequests: CustomerImportRequest, BulkUpdateRequest, CustomerDistributionRequest, ApproveCustomerUpdateRequest, RejectCustomerUpdateRequest, BulkCustomerUpdateRequestDecisionRequest, ExportCustomerUpdateRequestsRequest, StoreCustomerFollowRequest
Policy registration + abilities: CustomerPolicy, CustomerUpdateRequestPolicy registered in AuthServiceProvider
Actions / Services / Gateways: CustomerImportService, CustomerExportService, CustomerBulkUpdateService, CustomerDistributionService, CustomerUpdateRequestService, CustomerFollowService
Models / Enums / Events / Jobs: Customer, CustomerImportBatch, CustomerUpdateRequest, CustomerFollow
DataTable / index / filters / buttons: Update requests DataTable and Distribution preview tables
Create UI + draft resource key + restore/purge evidence: Excel upload wizard and distribution calculator form
Edit UI: Update request approval/rejection modal
Review step: Distribution target assignment preview and confirmation
Show UI + child tables + attachments: Import progress tracker and error summary view
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: bulk-operations.js, form-validation.js, file-upload.js
Files and storage lifecycle: Temporary Excel spreadsheet imports with auto-cleanup after job completion
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantAssetPolicyContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantAssetPolicyContractTest.php --filter="test_customer_distribution_protects_every_active_mutation_with_its_create_capability|test_customer_bulk_update_uses_the_existing_unscoped_edit_capability|test_customer_update_request_uses_typed_requests_and_record_policy" (3 passed, 15 assertions)
Not applicable items: Archive (bulk operations are transactional processes)
Remaining gaps: None
Status: Compliant
