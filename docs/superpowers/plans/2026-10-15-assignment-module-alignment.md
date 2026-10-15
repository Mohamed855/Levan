# Assignment Module Alignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Align the Tenant Assignments administration’s terminology, DataTable filters/status rendering, create/edit validation, selected-property cards, and attachment summaries with the project Golden Pattern.

**Architecture:** Retain persisted keys (`category`, `status`, `district`) and change only Arabic presentation labels and validation requirements. Keep all index filters as Select2 controls; date filters will use exact, available assignment dates supplied by the assignments service and applied server-side. Reuse the existing wizard’s selected-property and draft-file state so creation and review render one consistent card/summary contract.

**Tech Stack:** Laravel 11, Blade components, Yajra DataTables, Bootstrap 5, Select2, Vite, SCSS, PHPUnit.

**Spec:** User request in this conversation: Assignments administration custom alignment.

## Global Constraints

- Database identifiers remain English; `category` stays the persisted assignment classification key.
- The index has exactly five Select2 filters in this order: assignment classification, status, start date, end date, city.
- The `الحالة` table column remains immediately before actions and retains the shared `js-table-status-toggle` control.
- `district` is optional in both create and edit FormRequests and UI controls.
- Tenant draft files remain isolated in the tenant database and restore into the same UI contract.
- Changes to JavaScript or SCSS require `npm run build`.

## Review Focus

- An assignment with no `district` must create, validate through the wizard endpoint, and render without a required-field indicator.
- The two date filters must only return assignments matching their chosen exact start/end dates, including when both filters are active.
- Toggling assignment activity must refresh the status badge and switch to the canonical current status.
- A selected property without `main_image` must use the same tenant/platform fallback as its DataTable image.
- Empty, local-file, and restored-draft states must render `0 ملف` and the correct empty text or file count in both attachment boxes and the review step.

---

### Task 1: Align assignment terminology, optional district, and DataTable query contract

**Files:**
- Modify: `app/Services/Tenants/Assignments/AssignmentService.php`
- Modify: `app/DataTables/Tenants/Assignments/AssignmentsDataTable.php`
- Modify: `app/Http/Controllers/Tenants/Assignments/AssignmentsController.php`
- Modify: `app/Http/Requests/Tenants/Assignments/StoreAssignmentRequest.php`
- Modify: `app/Http/Requests/Tenants/Assignments/UpdateAssignmentRequest.php`
- Modify: `resources/views/tenants/assignments/index.blade.php`
- Modify: `resources/views/tenants/assignments/create.blade.php`
- Modify: `resources/views/tenants/assignments/edit.blade.php`
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`
- Test: `tests/Unit/DataTableContractTest.php`

**Interfaces:**
- Produces: `AssignmentService::dateFilterOptions(): array`, keyed by `Y-m-d` with Arabic display labels.
- Consumes: DataTable request keys `category`, `status`, `start_date`, `end_date`, and `city`.

- [ ] **Step 1: Write failing feature and DataTable-contract tests**

  Cover optional `district`, the two exact date filters, the five-filter order/labels, and the category terminology `تصنيف الإسناد`.

- [ ] **Step 2: Run focused tests to verify expected failures**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php tests/Unit/DataTableContractTest.php`

- [ ] **Step 3: Implement the backend and index filter contract**

  Add date options through `AssignmentService`, pass them from the controller, replace the obsolete range keys with exact `start_date`/`end_date` filters in `AssignmentsDataTable`, and render the five Select2 filters in the specified order. Change every assignment-facing `نوع الإسناد` label and validation attribute/message in create/edit to `تصنيف الإسناد`; make `district` nullable and remove its required UI marker.

- [ ] **Step 4: Run focused tests to verify green**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php tests/Unit/DataTableContractTest.php`

### Task 2: Render assignment status as badge plus shared toggle

**Files:**
- Modify: `app/DataTables/Tenants/Assignments/AssignmentsDataTable.php`
- Modify: `resources/views/tenants/assignments/index.blade.php`
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`

**Interfaces:**
- Consumes: `Assignment::$status_label`, `Assignment::$status_badge_class`, and the shared `table-status-toggled` event.
- Produces: a status cell with the canonical badge and, when permitted, `js-table-status-toggle`.

- [ ] **Step 1: Write a failing DataTable response assertion**

  Assert the assignment status cell includes the status label badge and the shared switch for an editor.

- [ ] **Step 2: Run the assertion to verify it fails**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

- [ ] **Step 3: Implement status-cell rendering and refresh**

  Render `status_badge_class`/`status_label` beside the reusable switch. Listen for the shared successful toggle event in the assignments index and reload only that DataTable page so the badge is canonical after a status change.

- [ ] **Step 4: Run the assignment feature tests**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

### Task 3: Unify selected-property cards and fallback images

**Files:**
- Modify: `app/DataTables/Tenants/Assignments/AssignmentPropertySelectionDataTable.php`
- Modify: `app/Http/Controllers/Tenants/Assignments/AssignmentsController.php`
- Modify: `resources/views/tenants/assignments/create.blade.php`
- Modify: `resources/assets/js/tenant/assignments/create.js`
- Modify: `resources/assets/scss/pages/assignment-create.scss`
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`

**Interfaces:**
- Consumes: property selection checkbox data (`id`, `number`, `name`, `type`, `city`, `image`) and the canonical tenant/platform table-image fallback.
- Produces: `renderPropertyCards(target, removable)` with a consistent detail-card design in selection and review.

- [ ] **Step 1: Write failing tests for property selection payload and create-view contract**

  Assert an image-less property returns/embeds the canonical fallback and the view exposes the selected-card fallback contract.

- [ ] **Step 2: Run the focused test to verify it fails**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

- [ ] **Step 3: Implement the unified property-card contract**

  Supply the same tenant/platform fallback used by the shared table image component, enrich selected-property payloads with displayed metadata, and redesign cards with image, code, name, metadata, and the existing remove control. Use the same renderer without removal controls in review.

- [ ] **Step 4: Run the focused test to verify green**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

### Task 4: Replace attachment lists with two counted summary boxes

**Files:**
- Modify: `resources/views/tenants/assignments/create.blade.php`
- Modify: `resources/assets/js/tenant/assignments/create.js`
- Modify: `resources/assets/scss/pages/assignment-create.scss`
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`

**Interfaces:**
- Consumes: existing `accumulatedFiles` and `restoredDraftFiles` maps for `general_documents` and `additional_attachments`.
- Produces: two boxed summaries (`مستندات عامة`, `مرفقات إضافية`) with dynamic `N ملف` counts, empty state, removable local/draft file cards, and matching review summaries.

- [ ] **Step 1: Write failing create-view and frontend-contract tests**

  Assert both boxes, their count targets, empty copy `لم يتم إرفاق ملفات بعد.`, and the corresponding review containers exist.

- [ ] **Step 2: Run the focused test to verify it fails**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

- [ ] **Step 3: Implement counted attachment summaries**

  Replace the bare list containers with two titled boxes. Update the file sync/render functions to count local and restored files, render the zero-file state, preserve removal behavior, and reuse the two-box visual structure in review. Preserve the existing primary image preview and assignment-letter form control.

- [ ] **Step 4: Run focused tests and build assets**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php --filter=assignment`

  Run: `npm run build`

### Task 5: Final module verification and simplification

**Files:**
- Review: all files modified in Tasks 1–4
- Test: `tests/Feature/TenantAssetModulesFeatureTest.php`
- Test: `tests/Unit/DataTableContractTest.php`

- [ ] **Step 1: Review the implementation for duplicated render state**

  Keep one property-card renderer and one attachment-summary renderer per file category; remove superseded filter keys and dead markup.

- [ ] **Step 2: Run final verification**

  Run: `php artisan test tests/Feature/TenantAssetModulesFeatureTest.php tests/Unit/DataTableContractTest.php`

  Run: `npm run build`

  Run: `git diff --check`

## Self-Review

- The plan covers each requested terminology, filter, status, optional-field, property-card, fallback-image, attachment-box, and review-state change.
- Exact-date Select2 options are the only new filter interpretation; they replace the unused `start_date_from`/`end_date_to` request keys.
- The two attachment boxes cover only the two dynamic multi-file categories named by the request; the image remains the wizard preview and the assignment letter remains its existing required upload control.
- Every UI state affected by selection, draft restoration, removal, and review has a matching test target.

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-10-15-assignment-module-alignment.md`. Please review the plan. I recommend **Native** execution because the DataTable, wizard, and draft-file UI share tight state and should be changed and verified together in this session. Does the plan capture what you want, and should I start implementation?
