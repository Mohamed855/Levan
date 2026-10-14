Administration: Tenant General Settings & Dictionaries
Boundary: tenant
Scope / aggregate root: Lookup dictionaries: Countries, Payment Methods, Contact Methods, Document Request Reasons, Customer Statuses, Message Templates
Routes + middleware + guard: routes/tenant/tenant.php (general-settings.*) + auth:tenant + permission per capability
Controller: App\Http\Controllers\Tenants\GeneralSetting\{Country,PaymentMethod,ContactMethod,DocumentRequest,CustomerStatus}\*Controller, MessageTemplateController
FormRequests: SaveGeneralSettingRequest, SaveDocumentRequestReasonRequest, SaveSettingsCustomerStatusRequest, StoreMessageTemplateRequest, UpdateMessageTemplateRequest, PreviewMessageTemplateRequest
Policy registration + abilities: Dedicated CountryPolicy, PaymentMethodPolicy, ContactMethodPolicy, DocumentRequestReasonPolicy, CustomerStatusPolicy, and MessageTemplatePolicy registered in AuthServiceProvider
Actions / Services / Gateways: GeneralSettingAction and MessageTemplateAction
Models / Enums / Events / Jobs: Country, PaymentMethod, ContactMethod, DocumentRequestReason, CustomerStatus, MessageTemplate
DataTable / index / filters / buttons: DataTables for each general setting dictionary with status switch and mass delete
Create UI + draft resource key + restore/purge evidence: Create modals and template editor with draft support
Edit UI: Edit modal / view per dictionary
Review step: Not applicable
Show UI + child tables + attachments: Preview template modal with live customer variable rendering
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, modal-crud-handler.js
Files and storage lifecycle: Not applicable
Tests: Tests\Unit\GeneralSettingsGoldenPatternContractTest, Tests\Feature\TenantCrudFeatureTest
Verification commands and passing evidence: php artisan test tests/Unit/GeneralSettingsGoldenPatternContractTest.php (6 passed, 27 assertions)
Not applicable items: Review step, archive, files
Remaining gaps: None
Status: Compliant
