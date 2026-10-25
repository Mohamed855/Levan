# Legacy Tenant and SuperAdmin Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the unused Tenant Teams administration and confirmed legacy debt-collection artifacts while preserving the active auction, asset, billing, domain, subscription, and support systems.

**Architecture:** The cleanup is dependency-first: routes, navigation, controllers, requests, actions, models, views, JavaScript, tests, and migrations are inspected together before a bounded legacy slice is removed. Central and tenant data boundaries remain unchanged; no active domain, billing, subscription, or payment integration is removed.

**Tech Stack:** Laravel, Blade, PHP 8, Stancl Tenancy, PHPUnit, Vite.

**Spec:** User-approved cleanup report in this conversation (2026-10-20).

## Global Constraints

- Keep active auctions, properties, assignments, subscriptions, bills, domains, Namecheap, Moyasar, tickets, and settings intact.
- Remove the Tenant Teams administration because the user confirmed it is not part of the product.
- Remove only debt-collection legacy artifacts whose runtime dependencies are absent.
- Use `apply_patch` for edits and deletions.
- Run `npm run build` if JavaScript or stylesheets change.
- Verify central and tenant migrations and focused/full tests before completion.

## Review Focus

- Route removal must not leave sidebar links, named-route calls, or permission seeding references.
- Removing legacy enums must not leave PHP imports or Blade enum references.
- Tenant context must remain correctly initialized for active migrations, seeders, jobs, and requests.
- The cleanup must not delete active billing/domain/payment classes merely because their names include payment or customer.
- Every removed migration/table reference must be absent from fresh tenant schemas and application code.

---

### Task 1: Remove Tenant Teams administration

**Files:**
- Modify: `routes/tenant.php`, tenant navigation/permission seeders/tests
- Delete: Team controller, requests, action/service, model, policy, DataTable, views, and team-specific JavaScript

- [ ] Write a route/navigation regression test proving Teams is absent.
- [x] Remove the Teams route group and sidebar/menu entries.
- [x] Remove all Team-specific implementation files and permissions.
- [ ] Run focused route and tenant authorization tests.

### Task 2: Remove confirmed debt-collection legacy artifacts

**Files:**
- Modify: activity-log mappings, tenant dashboard fragments, scheduler registration, documentation/tests
- Delete: unreferenced collection/customer/contract/import/general-setting enums and legacy-only classes

- [ ] Write or update inventory tests for the supported Tenant administration set.
- [ ] Remove only code whose references are exclusively legacy documentation/tests or the code itself.
- [ ] Remove obsolete scheduler references to absent collection/payment-plan commands.
- [ ] Run PHP lint and focused inventory tests.

### Task 3: Remove unbound SuperAdmin legacy administration code

**Files:**
- Delete/modify only after confirming no active route, service consumer, view, job, or test requires them: `APILayer`, SuperAdmin `NameCheap`, and `TransferRequest` slices.

- [ ] Add/adjust route inventory coverage for the supported SuperAdmin surface.
- [ ] Remove unbound controller slices and their exclusively-owned dependencies.
- [ ] Run route listing and focused SuperAdmin tests.

### Task 4: Reconcile schema, docs, and verification

**Files:**
- Modify: tenant migrations/seeders only when a removed administration owns schema/data.
- Modify: baseline documentation and tests to describe the current product.

- [ ] Remove remaining stale legacy references from migrations, seeders, docs, and tests.
- [ ] Run fresh central and tenant migrations, seed the test tenant, and run `composer test`.
- [ ] Run `npm run build` if any frontend asset changes.
