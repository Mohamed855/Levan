Administration: Tenant Customer Communications Subdomain
Boundary: tenant
Scope / aggregate root: Customer subresources: comments, notes, attachments, phone requests, SMS messaging, company demand letters
Routes + middleware + guard: routes/tenant/tenant.php (customers.{comments,notes,attachments,phones,letters,sms}.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerCommentController, CustomerNoteController, CustomerAttachmentController, CustomerPhoneRequestController, CustomerSmsController, CompanyLetterController
FormRequests: StoreCustomerCommentRequest, StoreCustomerNoteRequest, StoreCustomerAttachmentRequest, CustomerPhoneRequest, SendCustomerSmsRequest, PreviewCustomerSmsRequest, PreviewCompanyLetterRequest, SendCompanyLetterRequest
Policy registration + abilities: CustomerPolicy parent authorization plus CompanyLetterPolicy (viewAny/view/send) and nested-record ownership checks.
Actions / Services / Gateways: CustomerCommentService, CustomerNoteService, CustomerAttachmentService, CustomerPhoneRequestService, CustomerSmsService, CompanyLetterService
Models / Enums / Events / Jobs: CustomerComment, CustomerNote, CustomerAttachment, CustomerPhoneRequest, CustomerSms, CompanyLetter
DataTable / index / filters / buttons: Communication subtables inside Customer show page
Create UI + draft resource key + restore/purge evidence: Modal and inline subresource forms inside customer profile
Edit UI: Subresource edit modals
Review step: Not applicable
Show UI + child tables + attachments: Demand letter printable template preview
Archive controller / DataTable / views: Not applicable (subresource lifecycle cascades with parent Customer)
Shared components and JavaScript contracts: customer-communications.js, form-validation.js
Files and storage lifecycle: Customer attachments and letter PDFs on tenant disk with subresource parent verification
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantAssetPolicyContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantAssetPolicyContractTest.php --filter=customer_sms_and_company_letters (1 passed, 7 assertions) plus the existing customer subresource contract coverage.
Not applicable items: Standalone DataTable, review step, standalone archive
Remaining gaps: None
Status: Compliant
