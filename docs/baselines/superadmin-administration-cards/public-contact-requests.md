Administration: Central Public Contact Requests
Boundary: central
Scope / aggregate root: ContactRequest and its single reply workflow
Routes + middleware + guard: routes/admin/admin.php (admin.contact-requests.*) + auth:admin + contact_requests.view/reply
Controller: AppHttpControllersAdminPublicRequestsContactRequestController
FormRequests: AppHttpRequestsAdminPublicRequestsReplyContactRequest
Policy registration + abilities: AppPoliciesAdminPublicRequestsContactRequestPolicy (viewAny, view, reply)
Actions / Services / Gateways: AppActionsAdminPublicRequestsReplyToContactRequestAction
Models / Enums / Events / Jobs: ContactRequest, ContactRequestStatus, SendContactReplyMailJob
DataTable / index / filters / buttons: ContactRequestsDataTable with lifecycle stats, status filter, copy/excel toolbar, and shared table card
Create UI + draft resource key + restore/purge evidence: Not applicable; requests originate from the public site
Edit UI: Not applicable; reply is the only mutation
Review step: Not applicable
Show UI + child tables + attachments: shared two-column detail card with escaped message and shared textarea control
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: data-table.filters, ui.stat-card, form-validation.js, toastr
Files and storage lifecycle: Not applicable
Tests: TestsUnitAdminPublicRequestsGoldenPatternTest plus existing policy/action coverage
Verification commands and passing evidence: php artisan test tests/Unit/Admin/PublicRequestsGoldenPatternTest.php
Not applicable items: create/edit/archive/mass-delete because public requests are immutable workflow records
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
