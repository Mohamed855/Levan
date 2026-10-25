Administration: Tenant Support Tickets
Boundary: tenant
Scope / aggregate root: Customer technical support tickets, replies, attachment exchanges, feedback surveys
Routes + middleware + guard: routes/tenant.php (tickets.*) + auth:tenant + tickets permission
Controller: App\Http\Controllers\Tenants\Tickets\TicketController
FormRequests: TicketRequest, TicketReplyRequest, SubmitTicketSurveyRequest, RateTicketReplyRequest
Policy registration + abilities: Tenant route capabilities (tickets.view and tickets.create); ticket records remain in the central integration and are accessed only through the tenant-scoped gateway.
Actions / Services / Gateways: EtmamTicketService tenant-scoped integration gateway
Models / Enums / Events / Jobs: Ticket, TicketReply, TicketAttachment
DataTable / index / filters / buttons: TicketsDataTable (5 stat cards, filter by status/priority, export, add button)
Create UI + draft resource key + restore/purge evidence: resources/views/tenant/tickets/create.blade.php + draft support
Edit UI: Ticket status/priority update modal
Review step: Not applicable (single-card ticket form)
Show UI + child tables + attachments: resources/views/tenant/tickets/show.blade.php (two-column layout: ticket info + conversation thread)
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: tickets.js, form-validation.js
Files and storage lifecycle: Ticket attachments stored on tenant disk with mime verification
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantTicketAuthorizationContractTest, Tests\Unit\TenantTicketIsolationContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantTicketAuthorizationContractTest.php tests/Unit/TenantTicketIsolationContractTest.php (2 passed, 5 assertions)
Not applicable items: Review step, archive
Remaining gaps: None
Status: Compliant
