# Superadmin Subscriptions Pages Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all SQL/DataTable corruption, filter mismatches, security leaks, wizard/pricing/coupon discrepancies, dead scripts, and broken tests across Superadmin subscription pages (`/superadmin/subscriptions`, `/superadmin/subscriptions/create`, `/superadmin/subscription-manage/expiring`, `/superadmin/subscription-manage/expired`) and their companion subscription panels in Client Show, Plan Show, and Admin Sidebar.

**Architecture:** Keep the repository's "Golden Pattern": thin controllers delegating to `AbstractDataTable` subclasses with qualified `subscriptions.*` selects and `AbstractDataTable::applyDefaultDateFilters()`, domain logic in `AdminSubscriptionService` / `CouponService`, shared Blade components, and standard `@section('page-script')` bundles. Every DataTable fix preserves `DataTableDetailsControlContractTest` and `DataTableSelectionAndExportTest` contracts (`checkbox` first, `status` immediately before `actions` in status-toggle tables, `rawColumns` including `checkbox`).

**Tech Stack:** PHP `^8.2`, Laravel `^12.0`, `yajra/laravel-datatables` `^12.0`, Blade components, Bootstrap 5 RTL, Vite/ES6 modules, PHPUnit, Laravel Pint.

## Global Constraints

- All new/modified PHP files must use `declare(strict_types=1);` where already declared and explicit types on new signatures.
- Rely on `AbstractDataTable::applyDefaultDateFilters()` for `created_from`/`created_to` instead of duplicating unqualified `whereDate('created_at', ...)` calls.
- Escape all user-controlled text in HTML columns using `e(...)`.
- Never dump sensitive tenant credentials (`db_password`, `db_username`, `db_database`, `db_host`, `api_token`) into DataTable JSON payloads or Blade `@json(...)` globals.
- Keep `status` immediately before `actions` in `SubscriptionDataTable::getColumns()` (required by `DataTableDetailsControlContractTest`) and `checkbox` first in both DataTables (required by `DataTableSelectionAndExportTest`).
- Run targeted PHPUnit tests, `./vendor/bin/pint --test`, and `npm run build` after changes.

## Review Focus

- Ordering by a `belongsTo` relation (`tenant.company_name`, `tenant.tax_number`, `plan.name`) triggers Yajra `LEFT JOIN`: row `id` and `created_at` must remain `subscriptions.id` and `subscriptions.created_at`, never overwritten by `tenants` or `plans`.
- Combining `created_from`/`created_to` date filters with relation-column sorting (`tenant.company_name`, `plan.name`) must not throw `SQLSTATE[23000]: 1052 Column 'created_at' is ambiguous`.
- Selecting `status = active` on `/superadmin/subscription-manage/expired` must never expose active subscriptions on the Expired page.
- Submitting `/superadmin/subscriptions` with a soft-deleted tenant, invalid coupon, `pending` payment status, or non-toggleable subscription status (`expired`/`cancelled`) must validate cleanly and persist accurate bill/subscription states.
- Clicking status toggle or delete in `/superadmin/clients/{id}` must actually fire AJAX requests instead of silently doing nothing.

---

### Task 1: Fix `Subscription` Relations, Status Toggle Guard, Expiring Count & Cache Invalidation

**Files:**
- Modify: `app/Models/Admin/Subscription/Subscription.php:106-114`
- Modify: `app/Services/Admin/Subscriptions/AdminSubscriptionService.php:59-68,185-224`
- Modify: `app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php:115-143`
- Modify: `app/Actions/Admin/MassDelete/DeleteAdminRecordsAction.php:20-45`
- Modify: `app/Services/Admin/SubscriptionManagement/SubscriptionManagementService.php:79-116`
- Modify: `resources/views/admin/layouts/sections/menu/verticalMenu.blade.php:88-101`
- Test: `tests/Feature/CompanyAndSubscriptionRefactorTest.php`

**Interfaces:**
- Produces: `Subscription::tenant()` and `Subscription::plan()` using `->withTrashed()`
- Produces: `AdminSubscriptionService::clearSubscriptionCache(string $tenantId): void` (also clears `admin:expiring_subscriptions_count` and `admin:expired_subscriptions_count`)
- Produces: `AdminSubscriptionService::toggleStatus(Subscription $subscription): Subscription` throwing `\DomainException` when status is not `ACTIVE` or `SUSPENDED`

- [ ] **Step 1: Write failing tests for soft-deleted tenant visibility, toggle guard on expired subscription, expiring count without periods, and cache invalidation**

Add assertions to `tests/Feature/CompanyAndSubscriptionRefactorTest.php` (or a new dedicated test method in a new `tests/Feature/AdminSubscriptionsFixesTest.php`):
```php
public function test_subscription_relations_resolve_soft_deleted_tenant_and_plan(): void
{
    // Create tenant + plan + subscription, soft-delete tenant & plan, assert $subscription->fresh()->tenant !== null and $subscription->fresh()->plan !== null
}

public function test_toggle_status_rejects_expired_and_cancelled_subscriptions_with_422(): void
{
    // Subscription with status = EXPIRED -> PATCH /superadmin/subscriptions/{id}/toggle-status -> assertStatus(422)->assertJson(['success' => false])
    // Assert status remains EXPIRED
}

public function test_expiring_soon_count_includes_active_subscriptions_without_periods(): void
{
    // Active subscription with no periods and ends_at = now()->addDays(10) -> assert AdminSubscriptionService::getExpiringSoonCount() includes it
}
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: FAIL (relations return null for soft-deleted tenant, expired toggle returns 200 and flips to ACTIVE, expiring count omits periodless subscription).

- [ ] **Step 3: Update `Subscription.php` relations (`tenant` & `plan`)**

In `app/Models/Admin/Subscription/Subscription.php:106-114`, add `->withTrashed()` to `tenant()` and `plan()` so archived companies/plans remain visible in historical subscription listings.

- [ ] **Step 4: Update `AdminSubscriptionService.php`, `SubscriptionController.php`, `DeleteAdminRecordsAction.php`, `SubscriptionManagementService.php`, and `verticalMenu.blade.php`**

- In `AdminSubscriptionService::getExpiringSoonCount()` (`app/Services/Admin/Subscriptions/AdminSubscriptionService.php:59-68`), match `SubscriptionManagementService::getExpiringSoonCount()` by adding `->orWhere(fn ($q) => $q->whereDoesntHave('periods')->where('ends_at', '<=', now()->addDays(30))->where('ends_at', '>=', now()->startOfDay()))`.
- In `AdminSubscriptionService::toggleStatus()` (`app/Services/Admin/Subscriptions/AdminSubscriptionService.php:185-197`), guard with `if (! in_array($subscription->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::SUSPENDED], true)) { throw new \DomainException('لا يمكن تبديل حالة اشتراك منتهي أو ملغي أو قيد المراجعة.'); }` and update `SubscriptionController::toggleStatus()` (`app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php:115-143`) to catch `\DomainException` and return `422` JSON (`success => false, message => ...`) or redirect back with `error`, plus include `'is_active' => $updated->status === SubscriptionStatus::ACTIVE` in the `200` JSON response.
- In `AdminSubscriptionService::clearSubscriptionCache(string $tenantId)` (`app/Services/Admin/Subscriptions/AdminSubscriptionService.php:220-223`), also call `Cache::forget('admin:expiring_subscriptions_count'); Cache::forget('admin:expired_subscriptions_count');` and invoke it on `createSubscription()`, `toggleStatus()`, `deleteSubscription()`, and in `SubscriptionManagementService::accept()` / `reject()` / `massAccept()` / `massReject()` / `delete()`.
- In `DeleteAdminRecordsAction::execute()` (`app/Actions/Admin/MassDelete/DeleteAdminRecordsAction.php:20-45`), handle `if ($record instanceof Subscription)` by delegating to `app(AdminSubscriptionService::class)->deleteSubscription($record)` inside the transaction so bills are unlinked, periods/seats removed, and caches cleared consistently.
- In `resources/views/admin/layouts/sections/menu/verticalMenu.blade.php:94-99`, update `$expiredCount` to count `whereIn('status', [SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED])`.

- [ ] **Step 5: Run tests to verify Task 1 passes**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Admin/Subscription/Subscription.php app/Services/Admin/Subscriptions/AdminSubscriptionService.php app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php app/Actions/Admin/MassDelete/DeleteAdminRecordsAction.php app/Services/Admin/SubscriptionManagement/SubscriptionManagementService.php resources/views/admin/layouts/sections/menu/verticalMenu.blade.php tests/Feature/AdminSubscriptionsFixesTest.php
git commit -m "fix(subscriptions): preserve soft-deleted relations, guard status toggle, and sync caches"
```

---

### Task 2: Fix `SubscriptionDataTable` (`/superadmin/subscriptions`) Query, Ambiguous Filters, Status Display & Permissions

**Files:**
- Modify: `app/DataTables/Admin/Subscriptions/SubscriptionDataTable.php:19-229`
- Test: `tests/Feature/AdminSubscriptionsFixesTest.php`
- Test: `tests/Unit/DataTableDetailsControlContractTest.php`

**Interfaces:**
- Preserves: `SubscriptionDataTable::getTableId()` returning `'subscriptions-table'`
- Preserves: `checkbox` at index 0, `status` immediately before `actions` in `getColumns()`

- [ ] **Step 1: Write failing tests for sorting by `tenant.company_name` / `plan.name`, combining with `created_from`/`created_to`, `expiring_in`, and status badges**

In `tests/Feature/AdminSubscriptionsFixesTest.php`, add HTTP DataTable AJAX tests against `GET /superadmin/subscriptions` covering:
1. Ordering by column 2 (`tenant.company_name`), column 3 (`tenant.tax_number`), and column 4 (`plan.name`) returns `id === $sub->id` (NOT `0` and NOT `$plan->id`), omits `db_password` / `api_token` keys, and keeps `created_at` equal to subscription's `created_at`.
2. Ordering by column 2 (`tenant.company_name`) while sending `created_from=2026-01-01&created_to=2027-12-31` returns `200` with no `'error'` key in JSON.
3. Sending `expiring_in=30` only returns `ACTIVE` subscriptions expiring within 30 days (including periodless ones) and excludes `CANCELLED`/`EXPIRED`/`SUSPENDED`.
4. Row `status` column renders human-readable badge text (`منتهي`, `معلق`, `نشط`, etc.) alongside the toggle switch when toggleable (`ACTIVE`/`SUSPENDED`).

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: FAIL (row `id` becomes `0`/`1`, `db_password` leaks, `SQLSTATE[23000] 1052 Column 'created_at' is ambiguous`).

- [ ] **Step 3: Refactor `SubscriptionDataTable.php`**

- In `resource()` (`app/DataTables/Admin/Subscriptions/SubscriptionDataTable.php:19-74`): return ONLY the base query:
  ```php
  return Subscription::query()
      ->select('subscriptions.*')
      ->with([
          'tenant' => fn ($q) => $q->withTrashed()->with('city'),
          'plan' => fn ($q) => $q->withTrashed(),
          'currentPeriod',
          'periods',
      ]);
  ```
- Implement `protected function applyCustomFilters($query)` moving `status`, `plan_id`, `city_id`, `billing_cycle`, `start_from`, `end_to`, and `expiring_in` out of `resource()`, qualifying columns (`subscriptions.status`, `subscriptions.plan_id`, `subscriptions.starts_at`, `subscriptions.ends_at`), and removing duplicate `created_at` filters so `AbstractDataTable::applyDefaultDateFilters()` applies `subscriptions.created_at`.
- Update `expiring_in` filter in `applyCustomFilters($query)` to enforce `->where('subscriptions.status', SubscriptionStatus::ACTIVE)` and wrap `(currentPeriod active within $days OR (doesntHave periods AND subscriptions.ends_at within $days))`.
- Escape `company_name`, `tax_number`, and `plan_id` values safely; in `editColumn('status', ...)`, render the status badge (`<span class="badge bg-...">{{ label }}</span>`) for every row and append the `.js-table-status-toggle` switch only when `auth()->user()?->can('subscriptions.toggle_status')` AND `in_array($row->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::SUSPENDED], true)`.
- In `buttons()` (`app/DataTables/Admin/Subscriptions/SubscriptionDataTable.php:208-229`), use `$this->copyActionButton()`, `$this->excelActionButton()`, gate `حذف المحدد` on `auth('admin')->user()?->can('subscriptions.delete')`, and gate `إضافة اشتراك` on `auth('admin')->user()?->can('subscriptions.create')` using `<i class="ti ti-plus me-1"></i>`.

- [ ] **Step 4: Run tests including contract tests**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php tests/Unit/DataTableDetailsControlContractTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/DataTables/Admin/Subscriptions/SubscriptionDataTable.php tests/Feature/AdminSubscriptionsFixesTest.php
git commit -m "fix(subscriptions): prevent relation join column overwrite and ambiguous created_at in SubscriptionDataTable"
```

---

### Task 3: Fix `subscriptionMangmentDataTable`, `SubscriptionManagementController`, `admin/subscription-management/index.blade.php` & `AdminSubscriptionManagementFeatureTest`

**Files:**
- Modify: `app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:20-255`
- Modify: `app/Http/Controllers/Admin/SubscriptionManagement/SubscriptionManagementController.php:22-181`
- Modify: `resources/views/admin/subscription-management/index.blade.php:29-58`
- Modify: `tests/Feature/AdminSubscriptionManagementFeatureTest.php:97-143`
- Test: `tests/Feature/DataTableSelectionAndExportTest.php`
- Test: `tests/Unit/DataTableDetailsControlContractTest.php`

**Interfaces:**
- Consumes: `resources/views/admin/subscription-management/action.blade.php` (`['row' => $row]`)
- Preserves: `subscriptionMangmentDataTable::getTableId()` returning `'subscriptions-table'`

- [ ] **Step 1: Write/update failing feature tests for `expiring` and `expired` pages**

Update `tests/Feature/AdminSubscriptionManagementFeatureTest.php` to remove tests hitting deleted `/superadmin/subscription-manage/pending` and add thorough tests for `/superadmin/subscription-manage/expiring` and `/superadmin/subscription-manage/expired` (alongside keeping `accept`, `reject`, `massAccept`, `massReject` tests):
1. `GET /superadmin/subscription-manage/expiring` renders 200 without `#filter-sub-status`, and its AJAX load (with default order `[1, 'desc']` on `tenant.company_name`) returns real `subscriptions.id` (> 0), no `db_password`, valid `actions` column HTML, and `recordsFiltered` works with `created_from=2026-01-01` without SQL error.
2. `GET /superadmin/subscription-manage/expired` renders 200 without `#filter-expiring`, only exposes `[expired, cancelled]` status filter options, and sending `?status=active` on `/expired` never returns active subscriptions.

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test tests/Feature/AdminSubscriptionManagementFeatureTest.php`
Expected: FAIL (`id=0`, SQL 1052 with `created_from`, `status=active` returns active rows on `/expired`, missing `actions` column).

- [ ] **Step 3: Refactor `subscriptionMangmentDataTable.php`**

- In `resource()` (`app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:20-23`), add `->select('subscriptions.*')` and eager-load `['plan' => fn ($q) => $q->withTrashed(), 'currentPeriod', 'periods', 'tenant' => fn ($q) => $q->withTrashed()->with('city')]`.
- In `getColumns()` (`app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:25-55`), return `[...]` directly (matching `DataTableDetailsControlContractTest`'s regex), add `subscription_number` (aligned with `getSearchableColumns()`), add `actions` (`orderable: false, searchable: false`), and keep `checkbox` at index 0.
- In `addCustomColumns()` (`app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:57-147`), use `$row->periods->sortByDesc('period_end')->first()` (no N+1 query), escape `company_name`, `city`, `tax_number`, `plan_id` with `e()`, add `->addColumn('actions', fn ($row) => view('admin.subscription-management.action', ['row' => $row])->render())`, and update `rawColumns()` to `['checkbox', 'subscription_number', 'tenant_id', 'days_remaining', 'status', 'actions']`.
- In `applyCustomFilters($query)` (`app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:159-210`):
  - In `isExpiredRoute()`: always restrict to `[SubscriptionStatus::EXPIRED->value, SubscriptionStatus::CANCELLED->value]`; if `$request->filled('status')` and its value is in `[expired, cancelled]`, filter to that status, otherwise retain `whereIn('subscriptions.status', [EXPIRED, CANCELLED])`.
  - In `isExpiringRoute()`: qualify `subscriptions.status` = `ACTIVE`, `subscriptions.ends_at`.
  - Qualify `subscriptions.plan_id` and remove duplicate `created_at` filter (delegated to `AbstractDataTable::applyDefaultDateFilters()`).
- In `getActionsDropdownButton()` (`app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php:235-253`), gate `حذف المحدد` on `auth('admin')->user()?->can('subscriptions.delete')`.

- [ ] **Step 4: Update `SubscriptionManagementController.php` & `resources/views/admin/subscription-management/index.blade.php`**

- In `SubscriptionManagementController::expiring()` and `expired()`: set appropriate `$filterOptions['statuses']` (none for `expiring`; only `expired` & `cancelled` for `expired`).
- Remove dead/unrouted legacy methods (`index`, `create`, `planDuration`, `store`, `edit`, `update`, `destroy`) and unused imports from `SubscriptionManagementController.php` after confirming via grep/route-list that zero routes reference them.
- In `resources/views/admin/subscription-management/index.blade.php:44-57`, conditionally show `#filter-expiring` only when `$isExpiring`, and `#filter-sub-status` only when `! $isExpiring`.

- [ ] **Step 5: Run tests including contract tests**

Run: `php artisan test tests/Feature/AdminSubscriptionManagementFeatureTest.php tests/Feature/DataTableSelectionAndExportTest.php tests/Unit/DataTableDetailsControlContractTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/DataTables/Admin/SubscriptionMangment/subscriptionMangmentDataTable.php app/Http/Controllers/Admin/SubscriptionManagement/SubscriptionManagementController.php resources/views/admin/subscription-management/index.blade.php tests/Feature/AdminSubscriptionManagementFeatureTest.php
git commit -m "fix(subscription-management): fix expiring/expired filters, join id collision, actions column, and feature tests"
```

---

### Task 4: Fix Create Subscription Wizard Security, Coupon Validation, Pricing Parity & Step Validation (`/superadmin/subscriptions/create`)

**Files:**
- Modify: `routes/admin.php:119-126`
- Modify: `app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php:24-32,75-113,174-212`
- Modify: `app/Http/Requests/Admin/Subscriptions/StoreSubscriptionRequest.php:19-62`
- Modify: `app/Services/Admin/Subscriptions/AdminSubscriptionService.php:26-31,70-183`
- Modify: `resources/views/admin/subscriptions/create.blade.php:14-43`
- Modify: `resources/views/admin/subscriptions/partials/_form.blade.php:48-52,141-155`
- Modify: `resources/assets/js/admin/subscriptions/create-wizard.js:30-474`
- Test: `tests/Feature/SubscriptionCreateFormTest.php`
- Test: `tests/Feature/AdminSubscriptionsFixesTest.php`

**Interfaces:**
- Produces: Route `admin.subscriptions.validate-coupon` (`POST /superadmin/subscriptions/validate-coupon` -> `SubscriptionController::validateCoupon`)
- Produces: `AdminSubscriptionService::createSubscription(Tenant $company, array $data): Subscription` supporting `period_months`, `coupon_code` (via `CouponService`), `payment_status` (`completed` vs `pending`), `transaction_id`, and `admin_notes`

- [ ] **Step 1: Write failing tests for safe JSON payload, `validate-step`, coupon validation endpoint, `period_months` + `coupon_code` + `pending` status persistence**

In `tests/Feature/SubscriptionCreateFormTest.php` and `tests/Feature/AdminSubscriptionsFixesTest.php`:
1. `GET /superadmin/subscriptions/create` returns `200`, does NOT contain `db_password`, `db_username`, or `api_token` in HTML, does NOT include duplicate `form-validation.js`, and preselects `company_id` when `?company_id=...` is passed.
2. `POST /superadmin/wizard-drafts/validate-step` on Step 2 with `bank_transfer` and missing `receipt_upload` returns `422` with error ONLY on `receipt_upload` (not `payment_proof`).
3. `POST /superadmin/subscriptions/validate-coupon` validates an active coupon against calculated subtotal and returns `200` with `raw_discount` & `raw_total_after_discount`, or `422` on invalid coupon.
4. `AdminSubscriptionService::createSubscription()` with a monthly `100.00` plan, `period_months = 6`, valid `20%` coupon, `payment_status = 'pending'`, `transaction_id = 'TRX-999'`, `admin_notes = 'note'` creates:
   - `Subscription`: `price = 480.00`, `status = SubscriptionStatus::SUSPENDED`, `ends_at` = `start_date + 6 months`, `currentPeriod.status = PENDING_PAYMENT`.
   - `Bill`: `amount = 480.00`, `original_amount = 600.00`, `discount_amount = 120.00`, `coupon_code`, `status = BillStatus::UNPAID`, `paid_at = null`, `transfer_notes = 'note'`, `metadata['transaction_id'] = 'TRX-999'`.
   - Coupon `used_count` incremented by `1`.
   - Invalid coupon code throws `ValidationException` on `'coupon_code'`.

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test tests/Feature/SubscriptionCreateFormTest.php tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: FAIL.

- [ ] **Step 3: Secure `SubscriptionController::create()` and add `validateCoupon()` route/method**

- In `routes/admin.php:119-126`, add `Route::post('validate-coupon', [CentralSubscriptionController::class, 'validateCoupon'])->name('validate-coupon');`.
- In `SubscriptionController::__construct()` (`app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php:29`), include `'validateCoupon'` in `can:subscriptions.create` middleware (or `AdminSubscriptionCreate` request) and add `validateCoupon(Request $request, CouponService $couponService): JsonResponse` returning `200`/`422` JSON using `$couponService->validateAndCalculate(...)` (and inject `CouponService` in `AdminSubscriptionService`).
- In `SubscriptionController::create()` (`app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php:75-94`), prepare `$companiesPayload` containing ONLY `id`, `company_name`, `commercial_number`, `tax_number`, `address`, `city_id`, `city` (`['id', 'name']`), and `last_subscription` (`['subscription_number', 'plan' => ['name'], 'status' => label, 'ends_at' => system_date(...)]`), and pass `$plansPayload` with safe display fields; pass both to `admin.subscriptions.create`.

- [ ] **Step 4: Fix `StoreSubscriptionRequest.php` and `AdminSubscriptionService::createSubscription()`**

- In `StoreSubscriptionRequest::rules()` (`app/Http/Requests/Admin/Subscriptions/StoreSubscriptionRequest.php:42-55`), keep `'receipt_upload'` conditional requirement and remove `'payment_proof'` requiredIf duplication (or validate `'payment_proof'` only when supplied) and remove `'payment_proof'` from `_form.blade.php:151` `:fields`.
- In `AdminSubscriptionService::createSubscription()` (`app/Services/Admin/Subscriptions/AdminSubscriptionService.php:70-183`):
  - Resolve `endDate`: if `end_date` not provided and `period_months` is provided (for non-trial plans), compute `$startDate->copy()->addMonths((int) $data['period_months'])`; otherwise keep trial/plan fallback.
  - Resolve `basePrice`: remove non-existent `$plan->annual_offer_enabled` / `calculateAnnualOfferPrice()`; for non-trial plans scale `$plan->price_after_discount` by `period_months` when provided (`monthly` => `* months`, `quarterly` => `* (months / 3)`, `yearly` => `* (months / 12)`), rounded to 2 decimals.
  - Validate & apply `coupon_code` via `CouponService::processCoupon($couponCode, $basePrice)` (throwing `ValidationException` on `'coupon_code'` if `isset($couponResult['error'])`), combine with manual `discount_value` if `has_discount === 'yes'`, and call `CouponService::applyCoupon(...)` after bill creation.
  - Respect `$paymentStatus = $data['payment_status'] ?? 'completed'`: when `$isTrial`, keep `TRIAL`/`ACTIVE`/`PAID`; when `$paymentStatus === 'pending'` or `$paymentType === 'unpaid'`, set `SubscriptionStatus::SUSPENDED`, `SubscriptionPeriodStatus::PENDING_PAYMENT`, `BillStatus::UNPAID`, `paid_at = null`; otherwise `ACTIVE` + `BillStatus::PAID`.
  - Store `transaction_id` in `$bill->metadata['transaction_id']` and `admin_notes` in `$bill->transfer_notes` & `$bill->metadata['admin_notes']` (and drop non-existent `subscriptions.notes`).

- [ ] **Step 5: Fix `create.blade.php`, `_form.blade.php`, and `create-wizard.js`**

- In `resources/views/admin/subscriptions/create.blade.php:36-42`: pass `@json($companiesPayload)` and `@json($plansPayload)`, add `window.routes = Object.assign({}, window.routes || {}, { validateCoupon: "{{ route('admin.subscriptions.validate-coupon') }}", companyDetails: "{{ route('admin.subscriptions.company-details', ['company' => '__ID__']) }}" });`, and remove duplicate `'resources/assets/js/shared/form-validation.js'` from `@vite(...)`.
- In `resources/views/admin/subscriptions/partials/_form.blade.php:48-51,141-155`: pass `:value="old('company_id', request('company_id'))"` on `company_id`, remove `'payment_proof'` from Step 2 `:fields`.
- In `resources/assets/js/admin/subscriptions/create-wizard.js`:
  - Replace hardcoded `/admin/subscriptions/companies/${companyId}/details` with `window.routes.companyDetails.replace('__ID__', encodeURIComponent(companyId))`.
  - Format `end_date` locally (`YYYY-MM-DD`) instead of `toISOString().split('T')[0]`; when `plan_id` changes, set `period_months` default based on plan (`1` monthly, `3` quarterly, `12` yearly) and disable/lock `period_months` to `0`/hidden behavior for `trial_7_days`.
  - Compute VAT-inclusive breakdown matching `BillService::getPrintData()`: `totalWithVat = max(0, baseAmount - couponDiscount)`, `vat = totalWithVat * 0.15`, `priceWithoutVat = totalWithVat - vat`; update review badge for `payment_status` (`completed` => `مفعل فوراً` / `bg-success`, `pending` => `موقوف لحين السداد` / `bg-warning`).
  - Wire `#btn-validate-coupon` click handler to `POST` `window.routes.validateCoupon` with `coupon_code` + current `baseAmount`, show feedback in `#coupon-feedback`, and reset applied coupon discount when `plan_id`, `period_months`, or `coupon_code` changes.
  - Render `plan.features` using safe DOM node creation (`textContent`) instead of raw template HTML interpolation.

- [ ] **Step 6: Run tests**

Run: `php artisan test tests/Feature/SubscriptionCreateFormTest.php tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add routes/admin.php app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php app/Http/Requests/Admin/Subscriptions/StoreSubscriptionRequest.php app/Services/Admin/Subscriptions/AdminSubscriptionService.php resources/views/admin/subscriptions/create.blade.php resources/views/admin/subscriptions/partials/_form.blade.php resources/assets/js/admin/subscriptions/create-wizard.js tests/Feature/SubscriptionCreateFormTest.php tests/Feature/AdminSubscriptionsFixesTest.php
git commit -m "fix(subscriptions): secure create wizard payload, fix coupon/pricing calculation, and fix step validation"
```

---

### Task 5: Fix Companion Subscription Views & Shared Toggle Sync (`clients/show`, `plans/show`, `table-status-toggle.js`)

**Files:**
- Modify: `resources/views/admin/clients/show.blade.php:145-186,265-313`
- Modify: `resources/views/admin/plans/show.blade.php:285-288`
- Modify: `resources/assets/js/shared/table-status-toggle.js:26-37`
- Test: `tests/Feature/AdminSubscriptionsFixesTest.php`

- [ ] **Step 1: Write failing tests for `clients/show` script rendering & `plans/show` dates**

In `tests/Feature/AdminSubscriptionsFixesTest.php`:
1. `GET /superadmin/clients/{id}` HTML contains `confirmRecordDelete` and `js-sub-toggle` AJAX listener inside rendered `<script>` output, and calculates days remaining with `ends_at`/`trial_end_date` fallback and `0 يوم`/`منتهي` for `EXPIRED`/`CANCELLED`.
2. `GET /superadmin/plans/{id}` with an active subscription renders formatted `starts_at` and `ends_at` dates instead of `- إلى -`.

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: FAIL (`confirmRecordDelete` missing from `clients/show`, dates show `-` in `plans/show`).

- [ ] **Step 3: Apply fixes in `clients/show.blade.php`, `plans/show.blade.php`, and `table-status-toggle.js`**

- In `resources/views/admin/clients/show.blade.php`:
  - Replace `@push('scripts')` / `@endpush` (`L265, L313`) with `@section('page-script')` / `@endsection`.
  - Update `calculateDaysRemaining($period, $sub->ends_at ?? $sub->trial_end_date)` (`L150-152`) and treat `EXPIRED`/`CANCELLED` as `0`/expired (`L174-186`).
- In `resources/views/admin/plans/show.blade.php:286-287`, replace `$sub->start_date` and `$sub->end_date` with `$sub->starts_at` and `$sub->ends_at`.
- In `resources/assets/js/shared/table-status-toggle.js:31`, add `'suspended'` to falsy statuses (`['inactive', 'suspended', '0', 'false'].includes(value.toLowerCase())`).

- [ ] **Step 4: Run tests**

Run: `php artisan test tests/Feature/AdminSubscriptionsFixesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/clients/show.blade.php resources/views/admin/plans/show.blade.php resources/assets/js/shared/table-status-toggle.js tests/Feature/AdminSubscriptionsFixesTest.php
git commit -m "fix(subscriptions): fix client show subscription scripts, plan show dates, and suspended toggle sync"
```

---

### Task 6: Final End-to-End Verification & Frontend Build

**Files:**
- Verify: built assets via `npm run build` (only stage `public/build` if tracked in git — check `git status` first)

- [ ] **Step 1: Run all subscription, DataTable, and contract tests**

Run:
`php artisan test tests/Unit/Admin/ExpiringSubscriptionDaysRemainingTest.php tests/Feature/SubscriptionCreateFormTest.php tests/Feature/AdminSubscriptionManagementFeatureTest.php tests/Feature/AdminSubscriptionsFixesTest.php tests/Feature/DataTableSelectionAndExportTest.php tests/Unit/DataTableDetailsControlContractTest.php tests/Feature/SubscriptionDatesAndPeriodsLifecycleTest.php`
Expected: All pass with 0 failures.

- [ ] **Step 2: Run Pint code style check**

Run: `./vendor/bin/pint --test`
Expected: PASS with 0 style issues on modified PHP files.

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`
Expected: Build completes cleanly with updated `create-wizard.js` and `table-status-toggle.js` in `public/build/manifest.json`.

