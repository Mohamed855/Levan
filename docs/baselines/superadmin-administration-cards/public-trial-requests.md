Administration: Central Public Trial Requests
Boundary: central
Scope / aggregate root: TrialRequest and its approval/rejection workflow
Routes + middleware + guard: routes/admin.php (admin.trial-requests.*) + auth:admin + trial_requests.view/approve/reject
Controller: AppHttpControllersAdminPublicRequestsTrialRequestController
FormRequests: AppHttpRequestsAdminPublicRequestsRejectTrialRequest
Policy registration + abilities: AppPoliciesAdminPublicRequestsTrialRequestPolicy (viewAny, view, approve, reject)
Actions / Services / Gateways: ApproveTrialRequestAction and RejectTrialRequestAction
Models / Enums / Events / Jobs: TrialRequest, TrialRequestStatus, SendContactMailJob
DataTable / index / filters / buttons: TrialRequestsDataTable with lifecycle stats, status filter, copy/excel toolbar, and shared table card
Create UI + draft resource key + restore/purge evidence: Not applicable; requests originate from the public site
Edit UI: Not applicable; approve/reject are the only mutations
Review step: Not applicable
Show UI + child tables + attachments: shared two-column detail card with pending-only action group and shared reject form control
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: data-table.filters, ui.stat-card, form-validation.js, toastr
Files and storage lifecycle: Not applicable
Tests: TestsUnitAdminPublicRequestsGoldenPatternTest plus existing policy/action coverage
Verification commands and passing evidence: php artisan test tests/Unit/Admin/PublicRequestsGoldenPatternTest.php
Not applicable items: create/edit/archive/mass-delete because public requests are immutable workflow records
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
