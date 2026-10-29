Administration: Central Public Trial Requests
Boundary: central
Scope / aggregate root: verified TrialRequest, resumable tenant provisioning, planless seven-day subscription
Routes + middleware + guard: routes/admin.php (admin.trial-requests.*) + auth:admin + trial_requests.view/approve/reject; site.trial routes on web
Controller: App\Http\Controllers\Admin\PublicRequests\TrialRequestController, App\Http\Controllers\Site\TrialController
FormRequests: App\Http\Requests\Site\StoreTrialRequest, App\Http\Requests\Site\VerifyTrialOtpRequest, App\Http\Requests\Admin\PublicRequests\ApproveTrialRequest, App\Http\Requests\Admin\PublicRequests\RejectTrialRequest
Policy registration + abilities: App\Policies\Admin\PublicRequests\TrialRequestPolicy (viewAny, view, approve, reject)
Actions / Services / Gateways: App\Actions\Admin\PublicRequests\CreateTrialRequestAction, App\Actions\Admin\PublicRequests\ApproveTrialRequestAction, App\Actions\Admin\PublicRequests\RejectTrialRequestAction, App\Services\Shared\Registration\RegistrationOtpService
Models / Enums / Events / Jobs: App\Models\Admin\PublicRequests\TrialRequest, App\Models\Admin\Tenants\Tenant, App\Models\Admin\Subscription\Subscription, App\Models\Admin\Subscription\SubscriptionPeriod, App\Models\Admin\Licenses\LicenseSeat, App\Enums\Admin\PublicRequests\TrialRequestStatus
DataTable / index / filters / buttons: App\DataTables\Admin\PublicRequests\TrialRequestsDataTable
Create UI + draft resource key + restore/purge evidence: Not applicable; public trial requests originate from the site without draft support
Edit UI: Not applicable; requests are reviewed and approved/rejected
Review step: Not applicable
Show UI + child tables + attachments: Trial request details with client info, status, and generated login links
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: data-table.filters, ui.stat-card, form-validation.js, toastr
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\TrialRegistrationFlowTest, Tests\Feature\SiteCheckoutComponentContractTest, Tests\Feature\WebsiteFormValidationFeatureTest
Verification commands and passing evidence: php artisan test tests/Feature/TrialRegistrationFlowTest.php tests/Feature/SiteCheckoutComponentContractTest.php
Not applicable items: create/edit/archive/mass-delete because trial requests are public prospective tenant workflows
Remaining gaps: Baseline audit pending; complete full Golden Pattern rebuild and UI contract certification.
Status: Gap
