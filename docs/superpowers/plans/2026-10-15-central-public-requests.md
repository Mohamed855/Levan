# Central Public Requests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Persist public contact and trial requests in the Central Database and deliver complete Superadmin workflows for replying, approving, rejecting, and starting company creation.

**Architecture:** Central models and workflow enums will own the request lifecycle. Typed FormRequests, Policies, Actions/Services, DataTables, existing Blade components/modals, and queued mail jobs will follow the project's Golden Pattern. The existing company wizard will receive approved trial data through a signed/validated server-side flow without introducing a second company-creation implementation.

**Tech Stack:** Laravel, Eloquent, central MySQL connection, Yajra DataTables, Blade components, Bootstrap modals, queued mail jobs, PHPUnit/Pest test suite, Vite.

**Spec:** `docs/superpowers/specs/2026-10-15-central-public-requests-design.md`

## Global Constraints

- Central/Superadmin code uses `auth:admin` and the central database.
- Database keys, status values, permission names, route names, and codes are English only.
- Flow follows `Controller → FormRequest → Policy → Action/Service → UI → Tests`.
- No generic Repository layer and no Etmam API communication or fallback.
- Existing shared components, modals, validation, DataTable, toast, and wizard patterns must be reused.
- Any `.js`, `.css`, or `.scss` change requires `npm run build`.

## Review Focus

- Duplicate approval/rejection/reply requests must be blocked by workflow state checks and tested in the owning action/controller task.
- Rejection without a reason must fail FormRequest validation and be tested in the trial workflow task.
- Failed mail dispatch must not falsely report a successful reply; mail behavior and status transition must be tested in the contact workflow task.
- Public requests must never call Etmam; integration-removal tests must assert no connector/service path remains active.
- Trial approval must prefill only the agreed fields and preserve required company-wizard validation for all missing fields.

### Task 1: Central schema, models, and workflow enums

**Files:**
- Create: central migrations for `contact_requests` and `trial_requests`.
- Create: `app/Models/Admin/.../ContactRequest.php`, `TrialRequest.php`.
- Create: `app/Enums/Admin/.../ContactRequestStatus.php`, `TrialRequestStatus.php`.
- Create: model factories where the existing Admin test conventions require them.
- Test: central model/status unit tests and migration/schema feature coverage.

**Interfaces:**
- Produces central Eloquent aggregates with casts, fillable fields, status transitions, reply/rejection metadata, and indexes for status/created time.

- [ ] Write failing model and schema tests.
- [ ] Implement migrations, models, casts, relationships, and enums using English keys.
- [ ] Run focused model/schema tests.

### Task 2: Public submission actions and removal of Etmam flow

**Files:**
- Modify: `routes/site/site.php`.
- Modify: `app/Http/Controllers/Site/LandingPageController.php` and `TrialController.php`.
- Create: central create actions/services for contact and trial requests.
- Modify/remove: `EtmamTicketServiceWithOutTenant`, `EtmamConnectorWithOutTenant`, and only the related public-request integration usage.
- Modify: existing public request mail dispatch path as needed for locally persisted requests.
- Test: public submission feature tests and integration-removal tests.

**Interfaces:**
- Produces local `ContactRequest`/`TrialRequest` creation through typed request validation and redirects with existing toast behavior.

- [ ] Add failing tests proving valid submissions are stored centrally and invalid submissions are rejected.
- [ ] Replace Etmam calls with central create actions.
- [ ] Remove dead Etmam request code and verify no public Contact/Trial path references it.
- [ ] Run focused public form tests.

### Task 3: Policies, permissions, routes, and request classes

**Files:**
- Create: Central request policies.
- Modify: `app/Providers/AuthServiceProvider.php`.
- Modify: admin permission/role seeding using English permission keys.
- Modify: `routes/admin/admin.php`.
- Create: index/show/reply/reject/approve FormRequests as applicable.
- Test: policy and route authorization tests.

**Interfaces:**
- Produces protected named routes under `admin.contact-requests.*` and `admin.trial-requests.*` with explicit abilities for view, reply, approve, and reject.

- [ ] Write failing guest, unauthorized, authorized, method, and state-boundary tests.
- [ ] Implement policies, permissions, routes, and typed requests.
- [ ] Run route/policy tests.

### Task 4: Contact request administration

**Files:**
- Create: contact request DataTable, controller, list/show views, and action/service classes.
- Create/modify: reply action and existing queued mail pattern using textarea content.
- Create: contact request frontend orchestration only if needed; reuse shared form validation.
- Test: contact CRUD/list/show/reply feature tests and reply action unit tests.

**Interfaces:**
- Produces `index`, `show`, and `reply` workflow. A valid reply stores reply content, dispatches mail, and transitions status to `replied`; repeated replies are rejected by policy/workflow validation.

- [ ] Add failing tests for listing, details, required textarea, reply persistence, mail dispatch, and repeated reply rejection.
- [ ] Implement controller → request → policy → action/service → DataTable/UI flow.
- [ ] Reuse existing modal and textarea components.
- [ ] Run focused contact administration tests.

### Task 5: Trial request administration and company wizard handoff

**Files:**
- Create: trial request DataTable, controller, list/show views, approve/reject actions/services, and FormRequests.
- Modify: existing company creation controller/wizard handoff to accept validated prefill data.
- Test: trial list/show/approve/reject/idempotency and company-prefill feature tests.

**Interfaces:**
- Produces `index`, `show`, `approve`, and `reject` routes. Approval maps `customer_name` to the first admin name, `customer_email` to email, and `customer_phone` to phone; rejection requires and stores a reason.

- [ ] Add failing tests for pending-only actions, required rejection reason, approved/rejected state transitions, and prefilled wizard fields.
- [ ] Implement the workflow actions and secure server-side wizard handoff.
- [ ] Implement UI with existing modals and company wizard components.
- [ ] Run focused trial administration tests.

### Task 6: Trial plan seeding

**Files:**
- Modify: existing Admin plan seeder used by the project.
- Test: idempotent seeder/plan feature test.

**Interfaces:**
- Produces an active limited plan with English code and a seven-day trial duration using the existing `plans` schema and subscription conventions.

- [ ] Add failing test for idempotent seven-day trial plan creation.
- [ ] Implement/update the seeder without duplicate rows.
- [ ] Run the seeder test.

### Task 7: Golden Pattern manifest and final verification

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`.
- Create/modify: focused feature, unit, policy, and integration-removal tests as gaps are found.

- [ ] Record both administrations, all applicable layers, and explicit `not_applicable` reasons.
- [ ] Run focused request tests and the relevant existing Admin/company/mail test suites.
- [ ] Run `npm run build` after any JavaScript/CSS/SCSS changes.
- [ ] Run final route, syntax, and test verification before claiming completion.

