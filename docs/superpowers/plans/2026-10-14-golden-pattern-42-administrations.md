# Golden Pattern 42 Administrations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close and prove Golden Pattern compliance for the 42 remaining administrations, while regression-verifying the three already closed Tenant administrations: Assignments, Properties, and Auctions.

**Architecture:** Work by administration family, preserving Central and Tenant boundaries. Every mutation must be guarded by route middleware, a FormRequest authorization decision, and a Policy or explicit gateway scope; every write/file lifecycle stays in an Action or Service. Each family is only marked compliant when its manifest card, focused contract/feature tests, and global acceptance gates agree.

**Tech Stack:** Laravel 12, PHP 8.2, Spatie Permission, Stancl Tenancy, Yajra DataTables, Blade, Vite.

**Spec:** `AGENTS.md`, `docs/architecture/golden-pattern.md`, and `docs/baselines/golden-pattern-manifest.md`.

## Global Constraints

- The counted scope is 42 remaining administrations; Assignments, Properties, and Auctions are baseline-compliant and must be regression-verified.
- Use English database keys and permissions; Arabic belongs only in display/localization layers.
- Preserve Central/Tenant boundaries; Tenant code reaches Central state through explicit gateways only.
- Controller → FormRequest → Policy → Action/Service → UI → Tests.
- Every create form supports drafts; active index tables meet the shared DataTable/status-toggle contract.
- CSS/JS changes require `npm run build`; all delivered changes require the full Laravel test suite and `git diff --check`.

## Review Focus

- A request authorized by a route middleware but returning `true` from FormRequest authorization must be rejected by the request itself.
- A tenant-scoped identifier must not reveal or mutate a record from another tenant/user.
- External payment, DNS, and callback requests must be authenticated, idempotent where applicable, and configuration-only for secrets.
- Uploaded files must be validated, stored/cleaned through services, and never expose arbitrary paths.
- A manifest status must never be promoted without a focused proof and a passing global gate.

### Task 1: Establish the 42-administration inventory and acceptance ledger

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`
- Test: `tests/Feature/ActiveRouteHandlerContractFeatureTest.php`

- [ ] Add the explicit 42-administration scope, list the three regression-only closed administrations, and map each remaining family to its owner card.
- [ ] Make the route inventory test fail if a route family is absent from the compliance ledger.
- [ ] Implement the smallest manifest/test mapping needed for that assertion.
- [ ] Run `php artisan test --filter=ActiveRouteHandlerContractFeatureTest`.

### Task 2: Close Central/SuperAdmin administration families

**Files:**
- Modify: active Central controllers, FormRequests, Policies, Actions/Services, and DataTables only when an audited gap exists.
- Test: `tests/Unit/SuperAdminContractTest.php`, `tests/Unit/CentralPolicyContractTest.php`, relevant feature tests.

- [ ] For Dashboard, users/roles, plans, companies, subscriptions, billing, coupons, support, content, settings, versions, activity log, archive, mass delete, and API integration: add a failing family-specific test for the next gap.
- [ ] Implement the minimal policy/request/action/gateway/UI correction.
- [ ] Run the focused tests after each family; update the corresponding manifest card with evidence and named remaining gaps.

### Task 3: Close Tenant identity, configuration, and shared-operation families

**Files:**
- Modify: Tenant auth/settings/general-settings/notifications/archive/mass-delete/subscription-billing-license/ticket components as audited.
- Test: `tests/Unit/TenantAuthenticationContractTest.php`, `tests/Unit/TenantSettingsArchitectureContractTest.php`, boundary/isolation tests.

- [ ] Write a failing test for each uncovered authorization, ownership, draft, or Central-gateway gap.
- [ ] Implement only the necessary request, policy, service/action, or gateway change.
- [ ] Record the proof in the manifest card and run its focused suite.

### Task 4: Close Tenant business-center/customer families

**Files:**
- Modify: companies, contracts, portfolios, tasks, customers, customer communications, customer finance, and customer bulk-operation components as audited.
- Test: their feature/contract tests plus file-lifecycle and tenant-isolation tests.

- [ ] Add a failing test for each family’s next unproven requirement.
- [ ] Close request/policy/parent-ownership/file-lifecycle/job-owner gaps with minimal code.
- [ ] Verify focused tests and document evidence in the corresponding card.

### Task 5: Close Tenant collections, communication, reports, and domains

**Files:**
- Modify: collection, collection-process, collection-target, import, reconciliation, logs, reports, and domain/cPanel components as audited.
- Test: collection ownership, report export ownership, domain security, callback, and isolation tests.

- [ ] Use test-first remediation for each uncovered lifecycle/authorization/idempotency/external-integration gap.
- [ ] Ensure all external credentials remain in configuration and all export/file links are owner scoped.
- [ ] Verify the family and update the evidence card.

### Task 6: Re-verify the three closed administrations and run final acceptance

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md` only for evidence/status changes justified by tests.
- Test: full test suite and frontend build.

- [ ] Re-run focused Assignment, Property, and Auction lifecycle, draft, archive, DataTable, and isolation coverage.
- [ ] Re-read every one of the 42 cards, ensuring each required field has evidence or a justified `not_applicable` value.
- [ ] Run `php artisan test`, `npm run build`, and `git diff --check`.
- [ ] Report an exact compliant/partial/gap count; mark the objective complete only if all 42 are proven compliant.

## Execution Status

- [x] Existing cross-cutting route, FormRequest, policy, central-boundary, draft, DataTable, and storage contracts established.
- [x] Task 1 — 42-administration inventory and scope contract.
- [ ] Execute Tasks 2–6 in order, with a focused red-green cycle for each administration-family gap.
