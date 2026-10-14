Administration: Tenant Customer Finance Subdomain
Boundary: tenant
Scope / aggregate root: Customer debt collection payments, installment plans, debt settlements, payment history audits
Routes + middleware + guard: routes/tenant/tenant.php (customers.{payments,payment-plans,settlements,payment-history}.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerPaymentController, CustomerPaymentPlanController, CustomerSettlementController, CustomerPaymentHistoryController
FormRequests: StoreCollectionRequest, UpdateCollectionRequest, StoreCustomerPaymentPlanRequest, UpdateCustomerPaymentPlanRequest, StoreCustomerSettlementRequest, UpdateCustomerSettlementRequest
Policy registration + abilities: CustomerPolicy parent check + FinancialCollection permissions
Actions / Services / Gateways: CollectionService, CustomerPaymentPlanService, CustomerSettlementService
Models / Enums / Events / Jobs: CustomerPayment, PaymentPlan, PaymentPlanInstallment, Settlement, PaymentHistory
DataTable / index / filters / buttons: Payments and Installments subtables inside Customer show view
Create UI + draft resource key + restore/purge evidence: Payment registration modal with receipt upload and installment generator
Edit UI: Installment adjustment modal
Review step: Settlement terms confirmation dialog
Show UI + child tables + attachments: Payment receipt printable view
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: customer-finance.js, form-validation.js
Files and storage lifecycle: Bank transfer deposit receipts stored on tenant disk
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantAssetPolicyContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantAssetPolicyContractTest.php --filter="test_customer_payment_mutations_require_collection_capabilities_and_record_authorization|test_customer_finance_subresources_authorize_the_parent_customer" (2 passed, 15 assertions)
Not applicable items: Standalone DataTable, standalone archive
Remaining gaps: None
Status: Compliant
