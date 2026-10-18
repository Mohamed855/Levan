# Subscription-Owned Company Availability Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove the central tenant/company `status` field and make the latest subscription status the only source of company availability.

**Architecture:** The central `tenants` record will contain identity and provisioning data only. Tenant access middleware, admin statistics, company listings, and lifecycle tests will resolve availability from `lastSubscription.status`; subscription approval/rejection remains in subscription management.

**Tech Stack:** Laravel, Eloquent, Stancl Tenancy, PHPUnit/Pest feature tests, MySQL migrations.

**Spec:** User request in conversation: company status must be removed completely; subscription status controls whether the company is available.

## Global Constraints

- Preserve central/tenant database boundaries.
- Keep subscription status as the single English-keyed source of truth.
- Do not leave fallback reads or writes to `tenants.status`.
- Add a reversible data migration step before dropping the column only if required by deployment safety; the final schema must not contain the column.

## Review Focus

- Pending subscription: tenant login/access must be blocked by subscription state, not company state.
- Active subscription: access must remain available even though no company status exists.
- Suspended/expired subscription: existing subscription restrictions must continue to apply.
- Admin company statistics and filters: they must use subscription status and remain accurate.
- Existing records during migration: status values must be mapped into subscription state before the column is removed.

### Task 1: Inventory and failing regression tests

**Files:**
- Modify: `tests/Feature/CompanyAndSubscriptionRefactorTest.php`
- Modify: `tests/Feature/TenantDashboardPostRefactorCasesTest.php`
- Add/modify focused middleware and admin management tests as discovered by the inventory.

- [ ] Add failing assertions that tenant availability follows the latest subscription and that company status is no longer queried.
- [ ] Run focused tests and confirm failures are caused by the old company-status dependency.

### Task 2: Make runtime availability subscription-owned

**Files:**
- Modify: `app/Http/Middleware/Tenancy/CheckSubscription.php`
- Modify: `app/Services/Admin/Companies/CompanyService.php`
- Modify: `app/DataTables/Admin/Companies/CompanyDataTable.php`
- Modify: `app/Http/Controllers/Admin/Companies/CompanyController.php`
- Modify: affected admin/tenant views and services found by the inventory.

- [ ] Replace company-status checks, counts, filters, labels, and writes with latest-subscription status logic.
- [ ] Remove company status controls from UI and keep subscription controls in subscription management.
- [ ] Run focused tests and verify pending/active/suspended behavior.

### Task 3: Remove the schema field safely

**Files:**
- Add: `database/migrations/admin/<timestamp>_drop_status_from_tenants_table.php`
- Modify: `app/Models/Admin/Tenants/Tenant.php`
- Modify: seeders/factories/fixtures that still write `tenants.status`.

- [ ] Before dropping the column, map any legacy company status that has no subscription equivalent according to the existing subscription lifecycle rules.
- [ ] Drop `tenants.status` and remove it from model custom columns/fillable data.
- [ ] Run migration/schema verification and the full relevant test suite.

### Task 4: Final verification

- [ ] Run `php artisan test --filter=CompanyAndSubscriptionRefactorTest`.
- [ ] Run tenant subscription/access tests.
- [ ] Run `php -l` on changed PHP files.
- [ ] Search the repository to confirm no runtime `tenants.status` reads/writes remain.
