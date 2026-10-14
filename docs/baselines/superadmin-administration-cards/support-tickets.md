Administration: Central Technical Support Tickets
Boundary: central
Scope / aggregate root: Customer technical support tickets, staff responses, department assignment, ticket resolution
Routes + middleware + guard: routes/admin/admin.php (admin.tickets.*) + auth:admin + permission:tickets.*, system.settings
Controller: App\Http\Controllers\Admin\Tickets\AdminTicketController
FormRequests: App\Http\Requests\Admin\Tickets\ReplyTicketRequest, App\Http\Requests\Admin\Tickets\UpdateTicketStatusRequest, App\Http\Requests\Admin\Tickets\ToggleTicketsServiceRequest
Policy registration + abilities: App\Policies\Admin\Tickets\TicketPolicy registered in AuthServiceProvider for Ticket::class
Actions / Services / Gateways: App\Services\Admin\Tickets\AdminTicketService
Models / Enums / Events / Jobs: App\Models\Admin\Tickets\Ticket, App\Models\Admin\Tickets\TicketReply, App\Models\Admin\Tickets\TicketSection
DataTable / index / filters / buttons: App\DataTables\Admin\Tickets\AdminTicketsDataTable (status and priority filters, export buttons)
Create UI + draft resource key + restore/purge evidence: Not applicable (tickets created by tenants)
Edit UI: Status update in ticket show view
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/tickets/show.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: form-validation.js
Files and storage lifecycle: Ticket attachments stored on central storage disk
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_subscription_bill_and_ticket_actions_with_real_records"
Not applicable items: Create UI, drafts, review step, archive
Remaining gaps: None
Status: Compliant
