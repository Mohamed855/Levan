# SuperAdmin Administration Closure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all 19 SuperAdmin administration families against the Golden Pattern and certify each with an evidence card and passing regression tests.

**Architecture:** SuperAdmin is the central boundary. Controllers must use `Controller → FormRequest → Policy → Action/Service → UI → Tests`; tenant provisioning and tenant data access must go through explicit tenancy-aware services, never a controller shortcut. A card is marked `Compliant` only after its routes, request authorization, record policy, action/service transaction and file lifecycle, UI contract, and focused tests are documented.

**Tech Stack:** Laravel, PHP 8, Blade, Yajra DataTables, Spatie Permission, Stancl Tenancy, PHPUnit via `php artisan test`, Vite.

**Spec:** `docs/architecture/golden-pattern.md`, `docs/baselines/golden-pattern-manifest.md`, root `AGENTS.md`.

## Global Constraints

- Scope: 19 Central/SuperAdmin families: 11 Partial and 8 Gap at planning time.
- Central models stay in the central connection; tenant initialization, provisioning, deletion, and cross-boundary calls are explicit services/actions.
- All database/permission/status keys are English-only. UI labels are translated/display-layer strings.
- Every active mutation has one typed FormRequest with `authorize`, `rules`, `messages`, and `attributes`.
- Use record policies for model-bound operations and authorize every selected record in bulk/archival operations.
- No generic repository layer; Actions own single transactions and Services orchestrate workflows.
- Index/archive/DataTable/draft/validation contracts in `AGENTS.md` are mandatory; run `npm run build` after frontend assets change.
- Never promote an administration to `Compliant` without its own card, focused green test evidence, and the final global gate.

## Review Focus

- A central administrator changing their own role/status or deleting the final SuperAdmin — Task 3.
- A tenant provisioning, subscription approval, bill transition, or payment callback replaying twice — Tasks 4 and 5.
- A bulk/archive ID belonging to an unauthorized model or record — Task 8.
- A bank/domain/content upload leaving orphaned central storage on a database failure — Tasks 6 and 7.
- An external API failure exposing credentials, corrupting cache, or returning an unvalidated error payload — Task 9.

---

## Reviewed SuperAdmin Inventory and Closure Order

| Stream | Families | Current status | Closure criterion |
|---|---:|---|---|
| Platform foundation | Dashboard, Authentication/2FA, Activity Log | 3 Gap | capability-scoped read/mutation boundary, cache/service proof |
| Identity and catalogue | Admin Users/Roles, Plans, Coupons | 3 Partial | record policy, lifecycle actions, draft/DataTable/archive proof |
| Tenant commerce | Companies/Tenants, Subscriptions/Approvals, Billing | 3 Partial | explicit tenancy workflow, idempotent state changes, audit/file evidence |
| Support and content | Tickets, Content Clients, Content Portfolios | 1 Gap + 2 Partial | record/reply lifecycle, file transaction ownership, permissions |
| Central settings | Platform Settings, Bank Settings, Domain Settings | 1 Gap + 2 Partial | validated settings/cache/file action contracts |
| Release operations | Versions/Testers, Central Archive, Central Mass Delete, API Integrations | 1 Partial + 3 Gap | policy-per-record, gateway/retry/error contracts |

### Task 1: Reconcile SuperAdmin inventory and create 19 closure cards

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`
- Create: `docs/baselines/superadmin-administration-cards/<family>.md` (19 cards)
- Test: `tests/Unit/SuperAdminGoldenPatternInventoryTest.php`

**Interfaces:**
- Consumes: `php artisan route:list --json`, `routes/admin/admin.php`, `routes/admin/admin-auth.php`.
- Produces: 19 cards naming exact route/controller/request/policy/action/UI/test evidence and status.

- [x] Write a failing inventory test asserting exactly 19 Central/SuperAdmin families and 19 cards.
- [x] Build cards from active routes, policies, services/actions, DataTables, views, jobs, and tests; record `not_applicable` with a functional reason only.
- [x] Reconcile manifest rows without promoting any family prematurely.
- [x] Run the inventory test and confirm it passes.

### Task 2: Close Dashboard, Activity Log, and SuperAdmin authentication/2FA

**Files:**
- Modify: `app/Http/Controllers/Admin/{Dashboard,ActivityLog,Auth}/**`
- Modify/Create: `app/Http/Requests/Admin/{Dashboard,ActivityLog,Auth}/**`, actions/services/policies
- Test: `tests/Feature/AdminAuthenticationValidationFeatureTest.php`, `tests/Feature/AdminDashboardCrudFeatureTest.php`, `tests/Unit/SuperAdminContractTest.php`

**Interfaces:**
- Produces: `DashboardService`-owned KPI/cache reads, capability-scoped audit filters, and action-owned authenticated session/2FA mutations.

- [x] Write failing cases for invalid dashboard range, unauthorized audit filter/export, invalid 2FA confirmation, and session mutation on a non-admin guard.
- [x] Move any remaining dashboard cache calculation/auth persistence from controllers into services/actions; validate every filter.
- [x] Run the focused tests and attach evidence to all three cards.

### Task 3: Close Admin users, roles, and central authorization invariants

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminMangment/{Users,Roles}/**`
- Modify: `app/Policies/Admin/Users/AdminUserPolicy.php`, `app/Policies/Shared/RolePolicy.php`, FormRequests/actions/services
- Test: `tests/Feature/AdminDashboardCrudFeatureTest.php`, `tests/Unit/CentralPolicyContractTest.php`, `tests/Unit/SuperAdminContractTest.php`

**Interfaces:**
- Produces: guard-aware role lifecycle, per-record user authorization, self/last-SuperAdmin protection, and validated uniqueness endpoints.

- [x] Add failing tests for deleting/demoting the final SuperAdmin, a user modifying self-protected data, and a non-authorized role assignment.
- [x] Implement policy/action guards before persistence and keep password/image cleanup in services/actions.
- [x] Verify UI/DataTable status-toggle, drafts, and archive contract; run focused tests and close the card.

### Task 4: Close plans, companies/tenants, subscriptions, and approvals

**Files:**
- Modify: `app/Http/Controllers/Admin/{Plans,Companies,Subscriptions,SubscriptionManagement}/**`
- Modify/Create: Actions/Services/FormRequests/Policies for Plan, Tenant/Company, Subscription
- Test: `tests/Feature/AdminDashboardCrudFeatureTest.php`, new provisioning/idempotency feature tests

**Interfaces:**
- Produces: `CreateTenantAction`/subscription workflow services with explicit transaction, tenancy lifecycle, and approval state guards.

- [x] Write failing tests for invalid plan limits, duplicate tenant provisioning, repeated accept/reject callbacks, and subscription from another company.
- [x] Extract provisioning/approval writes and side effects to actions/services; authorize each bulk approval/rejection record.
- [x] Verify wizard drafts, plan features, company uniqueness and archive workflow; run focused tests and close four cards.

### Task 5: Close billing, coupons, and payment-state workflows

**Files:**
- Modify: `app/Http/Controllers/Admin/{Bills,Coupons}/**`, `app/Services/Shared/Bills/**`, central payment actions
- Modify: corresponding requests/policies/archive controllers
- Test: billing/coupon feature lifecycle cases and central policy contracts

**Interfaces:**
- Produces: explicit bill verify/reject/reactivate state transitions, idempotent payment handling, coupon applicability validation, and storage cleanup.

- [x] Write failing state-transition/replay tests and a coupon-plan scope test.
- [x] Implement status transitions in actions with transactions/audit events; authorize the bound bill/coupon before dispatch.
- [x] Verify standard redirect/JSON deletion responses, archive behavior and DataTable status toggle; close both cards.

### Task 6: Close tickets and central content management

**Files:**
- Modify: `app/Http/Controllers/Admin/Tickets/**`, `app/Services/Admin/Tickets/**`
- Modify: `app/Http/Controllers/Admin/ContentManagement/{Client,Portfolio}/**`, their actions/services/policies/requests
- Test: `tests/Feature/AdminDashboardCrudFeatureTest.php`, new ticket and file rollback feature tests

**Interfaces:**
- Produces: ticket record/reply/status policy proof and transactional central content/image file lifecycle.

- [x] Write failing tests for replying/updating a non-viewable ticket and an upload rollback after record failure.
- [x] Bind/authorize ticket records and delegate replies/status workflow to service/action; enforce content client/portfolio file ownership.
- [x] Confirm five-stat index/DataTable/draft/show/attachment layouts as applicable; run focused tests and close three cards.

### Task 7: Close platform, bank, and domain settings

**Files:**
- Modify: `app/Http/Controllers/Admin/Settings/**`, `app/Actions/Admin/**`, `app/Services/Admin/**`
- Modify: settings FormRequests, policies, DataTables, views
- Test: `tests/Feature/AdminDashboardCrudFeatureTest.php`, `tests/Unit/SuperAdminContractTest.php`

**Interfaces:**
- Produces: policy-authorized settings mutations, cache invalidation, bank/logo file actions, and central-domain status lifecycle.

- [x] Write failing tests for unauthorized update/toggle and file cleanup after failed bank/domain changes.
- [x] Move mutations/cache/files to actions; verify all FormRequests match controller capabilities.
- [x] Run settings feature/unit tests and complete the three cards.

### Task 8: Close versions/testers, central archive, and central mass delete

**Files:**
- Modify: `app/Http/Controllers/Admin/{Versions,VersionTester,Archive,DeleteBulk}/**`
- Modify: version services/actions, archive/mass-delete FormRequests, policies, `config/models.php`
- Test: `tests/Unit/CentralPolicyContractTest.php`, `tests/Unit/MassDeletePolicyContractTest.php`, archive feature cases

**Interfaces:**
- Produces: record-policy checks for every restore/force-delete/bulk selection and action-owned release/tester state changes.

- [x] Write failing tests for a disallowed bulk model/ID and unauthorized restore/force-delete.
- [x] Ensure each selected record is loaded and authorized; retain model allow-list and correct redirect/JSON responses.
- [x] Validate archive UI has no headers/stats, run focused tests, and close all three cards.

### Task 9: Close API integrations and external-provider contracts

**Files:**
- Modify: `app/Http/Controllers/Admin/APILayer/**`, `app/Services/Admin/APILayer/**`, config/request classes
- Test: new provider timeout/error/validation feature tests

**Interfaces:**
- Produces: validated request DTO/FormRequest, service-owned provider call, sanitized error response, bounded cache/retry behavior.

- [x] Write failing tests for malformed input, provider timeout, provider 5xx, and secret leakage in response/logs.
- [x] Move API calls/cache/retry out of the controller into a service and validate the response shape.
- [x] Run the provider tests and complete the API integration card.

### Task 10: SuperAdmin UI, drafts, DataTable, and accessibility closure sweep

**Files:**
- Modify: affected `resources/views/admin/**`, `resources/assets/js/**`, `resources/assets/scss/**`
- Test: `tests/Unit/CreateFormDraftContractTest.php`, `tests/Unit/DataTableContractTest.php`, feature views

**Interfaces:**
- Produces: every central index/create/edit/show/archive page meeting the shared UI and validation contract.

- [x] Inventory all central create forms and add failures for missing draft save/restore/purge wiring.
- [x] Apply canonical DataTable toolbar/status-toggle/archive layouts only to cards that still lack them.
- [x] Run `npm run build` after each frontend batch and run UI contract tests.

### Task 11: Certification gate and manifest closure

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`, all 19 SuperAdmin cards
- Test: `tests/Unit/SuperAdminGoldenPatternInventoryTest.php`, full suite

**Interfaces:**
- Consumes: evidence from Tasks 1–10.
- Produces: 19 certified `Compliant` SuperAdmin cards.

- [x] Add a failing certification test that requires every card’s route/request/policy/action/UI/test evidence before `Compliant`.
- [x] Run each card’s focused test command, `php artisan test`, `npm run build`, and `git diff --check`.
- [x] Reconcile the central manifest to 19 `Compliant` rows only after evidence is recorded.
- [x] Capture final command results in the manifest and rerun the certification test.

## Self-Review

- Tasks 2–9 cover each of the 19 audited SuperAdmin families; Task 10 covers shared presentation contracts and Task 11 is the only promotion gate.
- The review-focus inputs map to explicit tests in Tasks 3–9.
- Cross-boundary tenant work is constrained to explicit services/actions; controllers do not use a repository layer or direct persistence.
- No current Partial/Gap status is treated as completion merely because horizontal contracts are green.
