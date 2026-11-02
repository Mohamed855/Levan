# Form Request Validation Attributes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every validated request field a user-facing attribute name that exactly matches its active form label, while applying the approved fallback names to transport-only and internal fields.

**Architecture:** Keep Laravel FormRequest classes as the only validation-copy source. Add or complete each request's `attributes()` method without changing any custom `messages()` methods, and use inheritance where a child request already shares its parent form contract. A focused contract test will pin the confirmed label mappings and approved internal naming policy.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11.

**Spec:** `docs/audits/2026-11-02-validation-attributes-audit.md` and the user's approved naming decision in this conversation.

## Global Constraints

- Use the exact text shown by the active front-end label; retain English where the front end label is English.
- Keep every existing custom `messages()` value unchanged.
- Add attributes for request-only/internal fields using the approved, consistent fallback names.
- Preserve inherited attributes when a child request extends a request that owns the same form contract.
- Do not alter validation rules, authorization, routes, UI, or JavaScript.
- Check each target file for concurrent edits immediately before modifying it and stop for user direction if a target overlaps unrelated in-progress work.

## Review Focus

- Fields with custom validation messages still expose their attribute name for all default Laravel messages.
- Wildcard rules (`items.*`, `permissions.*`, uploads, and nested property data) use a matching wildcard attribute key.
- A request inheriting `rules()` also inherits a complete `attributes()` mapping rather than duplicating or losing it.
- Transport-only IDs, tokens, filters, and wizard payloads use the approved fallback terms without leaking sensitive values.
- Labels that deliberately appear in English in the UI remain English instead of being translated by inference.

---

### Task 1: Pin the naming contract before production edits

**Files:**

- Create: `tests/Unit/FormRequestAttributesContractTest.php`
- Reference: `docs/audits/2026-11-02-validation-attributes-audit.md`

**Interfaces:**

- Produces: a PHPUnit data provider that asserts each confirmed field resolves to its exact attribute name and each approved internal key resolves to its fallback term.

- [ ] **Step 1: Write the failing attribute-name contract test**

Cover one representative request from each boundary (Admin, Tenant, Site, Shared), a custom-message request, an inherited request, a wildcard rule, and the approved internal names (`ids.*`, `channel_name`, `socket_id`, wizard payload names, and reset tokens).

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Unit/FormRequestAttributesContractTest.php`

Expected: FAIL because audited requests currently omit or use labels that differ from the front end.

### Task 2: Complete Site and Shared request attributes

**Files:**

- Modify: `app/Http/Requests/Site/*.php`
- Modify: `app/Http/Requests/Shared/*.php`
- Modify: `app/Http/Requests/Shared/Wizard/*.php`
- Test: `tests/Unit/FormRequestAttributesContractTest.php`

**Interfaces:**

- Consumes: confirmed Site/Shared mappings in the audit, including checkout/trial inheritance and wizard transport names.
- Produces: complete labels for public checkout, trial, OTP, contact, broadcast, and draft validation messages.

- [ ] **Step 1: Add exact active-form mappings**

Add the confirmed labels for checkout/trial billing fields, contact fields, OTP, bank selection, coupon, and dynamic draft upload groups. Preserve `StoreTrialRequest` inheritance from `SubscriptionCheckoutRequest`.

- [ ] **Step 2: Add approved internal mappings**

Map broadcast (`channel_name`, `socket_id`) and wizard payload keys (`resource_key`, `file_key`, `step`, `target_path`, `target_method`, `fields`, `fields.*`) to the approved names, including unsupported/draft upload transport fields.

- [ ] **Step 3: Run the focused contract test**

Run: `php artisan test tests/Unit/FormRequestAttributesContractTest.php`

Expected: Site and Shared data sets PASS.

### Task 3: Complete Admin request attributes

**Files:**

- Modify: `app/Http/Requests/Admin/**/*.php`
- Test: `tests/Unit/FormRequestAttributesContractTest.php`

**Interfaces:**

- Consumes: exact visible labels and approved internal names from the Admin section of the audit.
- Produces: complete Admin request attributes for users, roles, clients, tickets, subscriptions, public requests, plans, coupons, banks, settings, activity, and bulk/order transports.

- [ ] **Step 1: Add visible-label mappings without changing custom messages**

Correct confirmed mappings such as `الاسم الكامل`, `الصلاحيات المتاحة`, `اسم المستخدم المسؤول`, ticket response/status labels, and all displayed settings/subscription/plan/coupon/bank labels.

- [ ] **Step 2: Add approved internal and wildcard mappings**

Map bulk IDs, ordering payloads, exclusion IDs, filters, dashboard range, designer payloads, permissions wildcards, reset transports, and attachment transports using the approved fallback terms.

- [ ] **Step 3: Run the focused contract test**

Run: `php artisan test tests/Unit/FormRequestAttributesContractTest.php`

Expected: Admin data sets PASS.

### Task 4: Complete Tenant request attributes

**Files:**

- Modify: `app/Http/Requests/Tenants/**/*.php`
- Test: `tests/Unit/FormRequestAttributesContractTest.php`

**Interfaces:**

- Consumes: exact visible labels and approved internal names from the Tenant section of the audit.
- Produces: complete Tenant request attributes for authentication, user/role management, tickets, settings, properties, assignments, auctions, payments, activity, domains, and internal action payloads.

- [ ] **Step 1: Apply exact labels for active tenant forms**

Use the confirmed label text for user/profile/password flows, tickets, banking, license checkout, assignments, properties, auctions, domain flows, activity filters, and resource status.

- [ ] **Step 2: Apply wildcard and internal naming policy**

Add matching wildcard mappings for nested properties, assignments, auctions, uploads, selected IDs, notifications, domain lookups/callbacks, generated property state, hidden reset data, and inactive-but-validated forms.

- [ ] **Step 3: Run the focused contract test**

Run: `php artisan test tests/Unit/FormRequestAttributesContractTest.php`

Expected: Tenant data sets PASS.

### Task 5: Verify completeness and scope

**Files:**

- Test: `tests/Unit/FormRequestAttributesContractTest.php`
- Reference: `docs/audits/2026-11-02-validation-attributes-audit.md`

**Interfaces:**

- Consumes: completed `attributes()` maps from all request boundaries.
- Produces: verified complete attribute coverage for audited request rules.

- [ ] **Step 1: Scan for validation rules that still have no attributes contract**

Run targeted `rg` checks for all FormRequests declaring `rules()` and reconcile the result with the audit, including inherited request classes.

- [ ] **Step 2: Run request contract and affected feature tests**

Run: `php artisan test tests/Unit/FormRequestAttributesContractTest.php tests/Unit/WebsiteFormValidationContractTest.php tests/Feature/TenantAuthenticationValidationFeatureTest.php tests/Feature/AdminAuthenticationValidationFeatureTest.php`

Expected: PASS.

- [ ] **Step 3: Run syntax and diff-scope checks**

Run: `php -l` for every modified PHP file and `git diff --check`.

Expected: no syntax errors or whitespace errors.

- [ ] **Step 4: Run the full automated suite and report unrelated failures transparently**

Run: `php artisan test`

Expected: PASS, or document each pre-existing/environmental failure separately without claiming a green suite.

- [ ] **Step 5: Review security and scope**

Confirm that no request data is logged, no validation/authorization behavior changed, and every edited file is an audited FormRequest or the dedicated contract test.
