# Tenant Administration Closure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring every Tenant administration to the Golden Pattern and prove it with an administration card, focused tests, stage gates, and a final full regression run.

**Architecture:** Treat Tenant as a modular monolith bounded by the tenant database and guard. Every mutation must follow `Controller → FormRequest → Policy → Action/Service → UI → Tests`; central data is accessed only through explicit tenant shared gateways. An administration becomes `Compliant` only when its manifest card contains route, request, policy, action/service, UI, storage/job and test evidence.

**Tech Stack:** Laravel, PHP 8, Blade, Yajra DataTables, Spatie Permission, Stancl Tenancy, PHPUnit/Pest via `php artisan test`, Vite.

**Spec:** `docs/architecture/golden-pattern.md`, `docs/baselines/golden-pattern-manifest.md`, root `AGENTS.md`.

## Global Constraints

- Scope: 21 remaining Tenant administration families; Assignments, Properties, and Auctions are regression-only because they are already `Compliant`. Collections are supporting Customer Finance workflows, not an independent administration.
- Tenant code must not directly access central models; use an explicit gateway and tenant context.
- Database keys, permissions, roles, status values and identifiers are English-only; UI Arabic is translated/display-layer only.
- FormRequest `authorize`, `rules`, `messages`, and `attributes` are the validation source of truth. No inline business validation in JavaScript.
- Every create form must support tenant-database drafts and discard them after successful creation.
- Indexes use the shared DataTable/status-toggle/mass-delete contracts; archives contain only filters/table card.
- Every `.js`, `.css`, or `.scss` change requires `npm run build` before the task closes.
- Never mark an administration compliant without its detailed manifest card and focused passing evidence.

## Review Focus

- Tenant-isolation bypass via a child route parameter belonging to a different parent record — test belongs to Tasks 6 and 7.
- Cross-tenant or cross-user download of temporary, exported, or report files — test belongs to Tasks 5, 8, and 10.
- Retry or duplicate callback/import processing changing the same balance, subscription, or license twice — test belongs to Task 9.
- Staff with a view-only lookup permission invoking a mutation — test belongs to Task 3.
- A non-manager or scoped user reaching a customer/collection record through an ID in a bulk request — test belongs to Tasks 6 and 7.

---

## Reviewed Tenant Inventory and Closure Order

| Stream | Families | Current evidence | Closure criterion |
|---|---:|---|---|
| Identity and platform | Authentication/2FA, Users/Teams/Roles, Dashboard/Charts, Settings/API tokens | 2 Gap, 2 Partial | guard/policy/action boundary and dashboard/cache proof |
| Tenant configuration | General Settings, Notifications/Activity, Archive, Mass Delete | 3 Gap, 1 Partial | record policies, owner isolation, archive/mass-delete tests |
| Tenant commercial boundary | Subscription/Billing/License/Versions, Tickets | 1 Gap, 1 Partial | central gateway, idempotency, tenant-scoped external records |
| Business center | Companies, Contracts, Portfolios, Tasks, Customers core | 1 Gap, 4 Partial | aggregate policy/action/archive/file lifecycle cards |
| Customer subdomains | Communications, Finance, Bulk Operations | 3 Gap | parent ownership, collection transaction invariants, owned jobs/files |
| Operations and reporting | Communication Logs, Reports, Domains/cPanel | 1 Partial, 2 Gap | export owner checks, gateway/provider boundaries |
| Closed regression | Assignments, Properties, Auctions | 3 Compliant | retain current compliance under full regression |

### Task 1: Reconcile Tenant inventory and create the 21 closure cards

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`
- Create: `docs/baselines/tenant-administration-cards/<family>.md` (21 cards)
- Test: `tests/Unit/TenantGoldenPatternInventoryTest.php`

**Interfaces:**
- Consumes: the active Tenant route inventory from `php artisan route:list --json`.
- Produces: one card per Tenant family with exact route/controller/request/policy/action/UI/test evidence and one of `Gap`, `Partial`, or `Compliant`.

- [x] Write a failing inventory test asserting exactly 24 Tenant families, exactly 3 named closed families, and exactly 21 remediation cards.
- [x] Run `php artisan test tests/Unit/TenantGoldenPatternInventoryTest.php` and confirm it fails before cards are added.
- [x] Build each card from `routes/tenant/tenant.php`, `AuthServiceProvider`, controllers, requests, policies, actions/services, views, jobs, and existing tests.
- [x] Add the cards and reconcile the Tenant rows in the manifest without changing a status to `Compliant`.
- [x] Run the inventory test and confirm it passes.

### Task 2: Close Tenant authentication, two-factor, users, teams, and roles

**Files:**
- Modify: `app/Http/Controllers/Tenants/Auth/**`, `app/Actions/Tenants/Auth/**`
- Modify: `app/Http/Controllers/Tenants/UserMangment/{User,Team,Role}/**`
- Modify: `app/Policies/Tenants/UserManagement/**`, `app/Providers/AuthServiceProvider.php`
- Modify/Create: matching `app/Http/Requests/Tenants/Auth/**` and `.../UserMangment/**`
- Test: `tests/Feature/TenantAuthenticationValidationFeatureTest.php`, `tests/Feature/TenantUserManagementValidationFeatureTest.php`, `tests/Unit/TenantAuthenticationContractTest.php`

**Interfaces:**
- Produces: guard-aware user/team/role policies and action-owned authentication persistence.
- Consumes: tenant guard and English Spatie permission keys.

- [x] Add failing feature tests for a wrong guard, a non-manager user mutation, cross-user profile image mutation, and guard-mismatched role assignment.
- [x] Implement missing record authorization and move any remaining persistence/session mutation into named actions.
- [x] Verify every active auth/user/team/role mutation has a dedicated FormRequest with the full contract.
- [x] Run the three focused test files; record commands/results in the five corresponding cards.

### Task 3: Close dashboard, chart settings, tenant settings, general settings, notifications, and activity logs

**Files:**
- Modify: `app/Http/Controllers/Tenants/{Dashboard,ChartSetting,Settings,Notification,activity_log}/**`
- Modify: `app/Actions/Tenants/{ChartSetting,Settings,Notification}/**`
- Modify/Create: matching requests and policies
- Test: `tests/Feature/TenantDashboardComprehensiveTest.php`, `tests/Unit/TenantSettingsArchitectureContractTest.php`, `tests/Unit/GeneralSettingsGoldenPatternContractTest.php`

**Interfaces:**
- Produces: validated dashboard/chart inputs, tenant-scoped cache keys, policy-protected settings and owner-scoped notification/activity operations.

- [x] Write failing cases for invalid KPI/chart range, a foreign notification/activity export, and a view-only lookup capability invoking a mutation.
- [x] Make cache keys tenant-prefixed and move settings/notification writes, cache invalidation, and files to Actions.
- [x] Keep General Settings FormRequest capabilities exactly aligned with the registered policy/seed capability; do not invent unseeded permissions.
- [x] Run focused feature/unit tests and document all six families.

### Task 4: Close archive and Tenant mass-delete administration families

**Files:**
- Modify: `app/Http/Controllers/Tenants/Archive/**`, `app/Http/Controllers/Tenants/DeleteBulk/MassDeleteController.php`
- Modify: archive/mass-delete FormRequests, policies, `config/models.php`
- Test: `tests/Unit/TenantArchivePolicyContractTest.php`, `tests/Unit/MassDeletePolicyContractTest.php`, feature archive cases

**Interfaces:**
- Produces: every record is authorized before restore, force-delete, or bulk delete; model mapping is explicit.

- [x] Add failing tests for a selected ID outside the authorized scope and a force-delete without the record ability.
- [x] Route operations through archive/mass-delete actions and preserve redirect/JSON response contracts.
- [x] Verify archive view layout has no title/stat cards and each deletion path owns storage cleanup.
- [x] Run archive and mass-delete tests; update the two cards.

### Task 5: Close subscription, billing, license, version, and Tenant ticket boundaries

**Files:**
- Modify: `app/Http/Controllers/Tenants/{Subscriptions,Bills,License,Versions}/**`, `app/Services/Tenants/Shared/Central*Gateway.php`
- Modify: `app/Http/Controllers/Tenants/Tickets/**`, `app/Services/Tenants/Tickets/**`
- Test: `tests/Feature/TenantDashboardPostRefactorCasesTest.php`, `tests/Unit/TenantTicketAuthorizationContractTest.php`, `tests/Unit/TenantTicketIsolationContractTest.php`, new billing idempotency feature tests

**Interfaces:**
- Produces: central interactions only through gateways, signed/idempotent callbacks, tenant-scoped ticket records/replies/files.

- [x] Write failing replay tests for payment/callback/upgrade work and foreign-tenant ticket tests.
- [x] Add idempotency keys/state guards in Actions/Services; do not put integration logic in controllers.
- [x] Verify callback signature/expiry/replay protection and export/download ownership.
- [x] Run focused commercial/ticket test files and attach evidence.

### Task 6: Close companies, contracts, portfolios, tasks, and core customers

**Files:**
- Modify: `app/Http/Controllers/Tenants/BusinessCenter/{Company,Contract,Portfolio,Customer}/**`, task controllers
- Modify/Create: corresponding Actions, FormRequests, Policies, archive controllers/DataTables
- Test: `tests/Feature/TenantCrudFeatureTest.php`, new aggregate-isolation feature tests

**Interfaces:**
- Produces: aggregate-specific policies, transactional Actions for files/relations, and canonical index/form/show/archive UI evidence.

- [x] Write failing tests for cross-company/portfolio/customer IDs, file rollback after an Action exception, and task assignee scope.
- [x] Implement policy authorization before service dispatch and transactional action ownership for record/file changes.
- [x] Update DataTables/forms/show/archive UI to the canonical component contract and verify drafts on every create form.
- [x] Run CRUD/isolation tests and complete five cards.

### Task 7: Close customer communications, customer finance, and customer bulk operations

**Files:**
- Modify: `app/Http/Controllers/Tenants/BusinessCenter/Customer/{Attachment,Comment,Note,PhoneRequest,Sms,Letter,Payment,PaymentPlan,Settlement,Follow,Import,BulkUpdate,Distribution,UpdateRequest}/**`
- Modify: related services/jobs/requests/policies
- Test: `tests/Unit/TenantAssetPolicyContractTest.php`, `tests/Feature/TenantDashboardComprehensiveTest.php`, new ownership/idempotency tests

**Interfaces:**
- Produces: parent-customer authorization for every child route, collection transaction invariants, owned temporary/upload/export files and jobs.

- [x] Expand the failing contract test to enumerate every active customer child controller and every mutation capability.
- [x] Add parent + nested-record checks before every service call, including SMS/letters/payment history and all bulk job/download routes.
- [x] Add transactional/idempotency guards for payment, settlement, import, bulk update, distribution, and update-request retry paths.
- [x] Verify files/job progress are tenant and initiating-user scoped; run customer-focused tests and complete all three cards.

### Task 8: Close collections, collection process, targets, imports, reconciliations, and communication logs

**Files:**
- Modify: `app/Http/Controllers/Tenants/FinancialCollection/**`, `app/Services/Tenants/FinancialCollection/**`
- Modify: `app/Http/Controllers/Tenants/Communication/MessageLog/**`, related policies/requests/jobs
- Test: collection process/import/report contract tests and dedicated workflow feature tests

**Interfaces:**
- Produces: collection state-machine transitions authorized at record level, manager-scoped customers, owner-scoped import/report files, and message-log export authorization.

- [x] Write failing tests for invalid state transitions, another manager’s customer, and another user’s import/error report.
- [x] Implement explicit actions for approve/reject/reconcile/import transitions and transaction/rollback semantics.
- [x] Verify DataTable status toggle and response contract; run focused tests and document both cards.

### Task 9: Close reports and domains/cPanel

**Files:**
- Modify: `app/Http/Controllers/Tenants/Reports/**`, `app/Services/Tenants/Reports/**`, `app/Jobs/Tenants/Reports/**`
- Modify: `app/Http/Controllers/Tenants/Domains/**`, `app/Services/Tenants/Domains/**`, `app/Services/Tenants/Shared/CentralDomainGateway.php`
- Test: `tests/Unit/TenantReportExportAccessContractTest.php`, domain/gateway feature tests

**Interfaces:**
- Produces: validated report filters, tenant/user-owned generated files, provider requests behind services, and payment/transfer renewal idempotency.

- [x] Write failing tests for invalid filters, foreign exported file retrieval, provider failure, and domain payment replay.
- [x] Move provider/cPanel/DNS calls and callback state changes into services/actions; enforce gateway-only central access.
- [x] Confirm generated report file ownership is recorded before response/job dispatch.
- [x] Run report/domain tests and complete both cards.

### Task 10: Re-certify Assignments, Properties, and Auctions

**Files:**
- Review: `app/Http/Controllers/Tenants/{Assignments,Properties,Auctions}/**`, associated actions/policies/views/tests
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`, `tests/Feature/PropertyManagementFeatureTest.php`

**Interfaces:**
- Produces: regression proof that the three previously closed Tenant administrations retain `Compliant` status.

- [x] Run current asset/property feature suites before modifying dependent shared components.
- [x] Repair any regression using test-first changes only.
- [x] Revalidate their cards against the current Golden Pattern and record fresh evidence.

### Task 11: Tenant UI, draft, DataTable, and accessibility closure sweep

**Files:**
- Modify: affected `resources/views/tenants/**`, `resources/assets/js/**`, `resources/assets/scss/**`
- Test: `tests/Unit/CreateFormDraftContractTest.php`, `tests/Unit/DataTableContractTest.php`, browser/feature tests where present

**Interfaces:**
- Produces: all Tenant index/create/edit/show/archive pages meet the shared UI contract without duplicated browser validation.

- [x] Inventory Tenant create forms and write failures for absent draft action/restore/purge wiring.
- [x] Add canonical five-stat index cards, toolbar add/mass-delete controls, status-toggle placement, and archive layout only where the card says the UI is incomplete.
- [x] Run `npm run build` after every frontend batch and run UI contract tests.

### Task 12: Certification gate and manifest closure

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`, all 21 Tenant cards
- Test: full test suite and Tenant inventory/certification tests

**Interfaces:**
- Consumes: passing evidence from Tasks 1–11.
- Produces: 21 `Compliant` Tenant cards plus three re-certified closed cards.

- [x] Add a failing certification test that refuses `Compliant` without every required evidence field and a passing focused test command.
- [x] Run each card’s exact test command, `php artisan test`, `npm run build`, and `git diff --check`.
- [x] Update card status only after evidence is present; reconcile the Tenant manifest counts to 24 families (21 closed in this programme + 3 regression-certified).
- [x] Run the final certification test and capture the full command output in the manifest evidence section.

## Self-Review

- Scope coverage: Tasks 2–9 cover all 21 remaining Tenant families; Task 10 keeps the three excluded closed families valid; Task 11 covers cross-family UX contracts; Task 12 proves closure.
- Isolation, owner-file access, idempotency, capability mismatch, and scoped-user inputs are assigned to explicit tests in Tasks 3–9.
- No task relies on a generic repository; mutation ownership stays in Actions/Services and central access stays in gateways.
- The plan deliberately does not promote current Partial work to Compliant without administration cards and focused evidence.
