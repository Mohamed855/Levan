# Standardize All Edit Views to Match Create Views

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize all `edit.blade.php` views across Admin and Tenant modules to match the exact stepper/form design of their corresponding `create.blade.php` views, removing draft buttons, prefilling backend data, and locking non-editable fields with disabled/readonly inputs alongside hidden fallbacks.

**Architecture:** Each edit view will mirror its corresponding create view layout (same wizard steps, form controls, and asset loads) while targeting the `update` route using `@method('PUT')`. Non-editable identifiers (e.g. employee numbers, assignment numbers, property numbers, coupon codes) are rendered as `readonly` or visually `disabled` with hidden input fallbacks to preserve Laravel FormRequest validation compatibility. Draft-saving actions and wizard draft buttons are completely omitted from edit views.

**Tech Stack:** Laravel 11, Blade Components (`<x-form...>`, `<x-ui...>`), Bootstrap 5, BS-Stepper, jQuery, SCSS, Vite.

**Spec:** Client requirement: "كل صفحات الedit تكون بنفس تصميم صفحات الcreate بالظبط بس بدون زر حفظ كمسودة و تعرض البيانات المبعوثة من الباك و الأجزاء التي لا تُعدل تكون موجودة و ظاهرة كـ disabled inputs".

---

## Global Constraints

- Never break backend validation: any visually `disabled` input that is `required` in a FormRequest MUST have an accompanying `<input type="hidden" name="field" value="...">` or use `readonly`.
- Never include `<x-form.wizard.draft-button />` or `data-draft-url` in any edit view.
- Always include `@csrf` and `@method('PUT')` in every edit form.
- Always use `tenant_route_name(...)` for Tenant routes and `route(...)` for Admin routes.
- Always use `tasset(...)` for Tenant assets and `asset('storage/...')` for Admin assets.
- Maintain consistent button styling: save button in edit views must say "تحديث" or "حفظ التعديلات" (`<x-form.wizard.save-button text="تحديث" />` or `<button type="submit" class="btn btn-primary">تحديث</button>`).

---

## Review Focus

1. **Missing payloads for disabled fields:** FormRequests failing with validation errors because disabled inputs were omitted from HTTP PUT request payload. Tested by verifying hidden inputs exist for every disabled field.
2. **Draft button artifacts:** Draft buttons or draft auto-save endpoints remaining in edit forms. Tested by asserting zero instances of `draft-button` or `wizard-drafts.store` in edit views.
3. **Stepper step mismatch:** An edit view having fewer steps than its create counterpart (e.g. Assignments or Auctions). Tested by verifying step IDs and counts match create.blade.php.
4. **Asset path resolution:** Tenant image previews failing because `asset('storage/...')` was used instead of `tasset(...)`. Tested by inspecting image/logo preview tags.
5. **Pre-fill accuracy with `old()` fallback:** Missing `old('field', $model->field)` fallback causing edited values to reset when validation fails. Tested by ensuring `old()` helper or `:value` binds model attributes.

---

### Task 1: Standardize Tenant User Management Edit Views

**Files:**
- Modify: `resources/views/tenant/user-management/users/edit.blade.php`
- Modify: `resources/views/tenant/user-management/roles/edit.blade.php`
- Modify: `resources/views/tenant/user-management/permissions/edit.blade.php`

**Interfaces:**
- Consumes: `$user` with roles and employee number, `$roles` list, `$role` Spatie model with permissions, `$permission` model.
- Produces: Normalized 2-step stepper for Users, card layout for Roles with locked slug, and single-card form for Permissions.

- [ ] **Step 1: Update `tenant/user-management/users/edit.blade.php`**
  - Lock `employee_number` using `readonly` and styled as read-only.
  - Ensure `name`, `email`, `phone`, and `role_id` are prefilled.
  - Verify zero `draft-button` components exist.
  - Use `tasset($user->image)` for profile image preview.
  - Set submit button text to "تحديث".

- [ ] **Step 2: Update `tenant/user-management/roles/edit.blade.php`**
  - Lock `name` (the English slug) as `readonly` with informative help text so existing role identifiers cannot be mutated.
  - Ensure zero draft buttons are present.
  - Preserve all permission accordion checkboxes and select-all JavaScript logic with `$role->hasPermissionTo(...)`.

- [ ] **Step 3: Update `tenant/user-management/permissions/edit.blade.php`**
  - Ensure zero draft button exists.
  - Pre-fill `name` attribute using `old('name', $permission->name)`.
  - Set submit button to "تحديث".

- [ ] **Step 4: Verify Tenant User Management Views**
  - Check syntax with `php -l` or Blade compilation test.
  - Confirm absence of draft buttons and presence of PUT method.

---

### Task 2: Standardize Tenant Business Center Companies Edit View

**Files:**
- Modify: `resources/views/tenant/business-center/companies/edit.blade.php`

**Interfaces:**
- Consumes: `$company` with `authorizedPeople`, `logo_url`, `currentTarget`.
- Produces: Standardized stepper matching `companies/create.blade.php`, with prefilled company name, target percentage, logo with `tasset()`, and dynamic authorized people list.

- [ ] **Step 1: Refactor `tenant/business-center/companies/edit.blade.php`**
  - Convert to use standard `<x-form.wizard.stepper>` instead of raw HTML `bs-stepper` if applicable, matching create layout.
  - Remove all draft buttons (`<x-form.wizard.draft-button />` and `data-draft-url`).
  - Pre-fill `name`, `target_percentage`, and current target alert info.
  - Populate existing `authorized_people` into the dynamic list with `is_default` radio pre-selection.
  - Ensure submit button is styled as primary with text "تحديث".

- [ ] **Step 2: Verify Companies Edit View**
  - Check form action points to `tenant_route_name('companies.update', $company)`.
  - Verify `@method('PUT')` is present.

---

### Task 3: Standardize Tenant Properties Edit View

**Files:**
- Modify: `resources/views/tenant/properties/edit.blade.php`

**Interfaces:**
- Consumes: `$property` with boundaries, location, ownership, attachments.
- Produces: Full 5-step wizard matching `properties/create.blade.php`:
  1. Basic info (`property-basic-validation`)
  2. Location & boundaries (`property-location-validation`)
  3. Ownership & details (`property-ownership-validation`)
  4. Files & photos (`property-files-validation`)
  5. Review & save (`property-review-validation`)

- [ ] **Step 1: Update `tenant/properties/edit.blade.php` to match Create 5-step Stepper**
  - Make `property_number` visible in Step 1 as a `readonly` disabled-styled input, plus hidden input `<input type="hidden" name="property_number" value="{{ $property->property_number }}">`.
  - Ensure all 5 steps match `properties/create.blade.php` structure and field names.
  - Pre-fill every field with `old('field', $property->field)`.
  - Remove any `<x-form.wizard.draft-button />` and `data-draft-url`.
  - Set the final step save action to "تحديث العقار".

- [ ] **Step 2: Verify Properties Edit View**
  - Confirm step validation URLs and fields array match create.blade.php.
  - Verify `@method('PUT')` is in form header.

---

### Task 4: Standardize Tenant Assignments Edit View

**Files:**
- Modify: `resources/views/tenant/assignments/edit.blade.php`

**Interfaces:**
- Consumes: `$assignment` with loaded properties, `cityOptions`.
- Produces: Full wizard layout mirroring `assignments/create.blade.php` while adapting property selection and files to update requirements.

- [ ] **Step 1: Re-architect `tenant/assignments/edit.blade.php` to Mirror Create View**
  - Expand from 2 steps to full wizard structure matching `assignments/create.blade.php`.
  - Render `assignment_number` in basic info as a `readonly` input with hidden input fallback.
  - Remove any draft button.
  - Show assigned properties and allow management or review.
  - Pre-fill notes, dates, entity, status, and category.
  - Include submit button with text "تحديث الإسناد".

- [ ] **Step 2: Verify Assignments Edit View**
  - Verify form method `@method('PUT')` and action `tenant_route_name('assignments.update', $assignment->id)`.

---

### Task 5: Standardize Tenant Auctions Edit View

**Files:**
- Modify: `resources/views/tenant/auctions/edit.blade.php`

**Interfaces:**
- Consumes: `$auction`, `$assignments`, `$properties`, `$cityOptions`, `$templateOptions`.
- Produces: 5-step wizard mirroring `tenant/auctions/create.blade.php`:
  1. `step-basic-info` (name, dates, times, type, category, city)
  2. `step-assignment` (assignment selection, platform)
  3. `step-properties` (linked properties selection)
  4. `step-settings` (template selection, brochure/billboard/social, cover image)
  5. `step-review` (summary review card)

- [ ] **Step 1: Refactor `tenant/auctions/edit.blade.php` into Full 5-Step Stepper**
  - Mirror the 5 steps of `tenant/auctions/create.blade.php`.
  - Lock `assignment_id` if already assigned, or show as `readonly`/selected with hidden input if locked.
  - Pre-fill basic info, dates, times, and pre-select current template IDs.
  - Remove `<x-form.wizard.draft-button />` and draft URLs.
  - Set submit button to "تحديث المزاد".

- [ ] **Step 2: Verify Auctions Edit View**
  - Verify action `tenant_route_name('auctions.update', $auction->id)`.
  - Confirm all 5 steps render seamlessly with `@method('PUT')`.

---

### Task 6: Standardize Admin Edit Views (Users, Roles, Clients, Coupons, Plans, Settings)

**Files:**
- Modify: `resources/views/admin/users/edit.blade.php`
- Modify: `resources/views/admin/roles/edit.blade.php`
- Modify: `resources/views/admin/clients/edit.blade.php`
- Modify: `resources/views/admin/coupons/edit.blade.php`
- Modify: `resources/views/admin/plans/edit.blade.php`
- Modify: `resources/views/admin/settings/banks/edit.blade.php`
- Modify: `resources/views/admin/settings/domains/edit.blade.php`

**Interfaces:**
- Consumes: Respective Admin models (`$user`, `$role`, `$client`, `$coupon`, `$plan`, `$bank`, `$domain`).
- Produces: Fully synchronized edit views matching their create views without draft buttons and with locked immutable fields.

- [ ] **Step 1: Update `admin/users/edit.blade.php`**
  - Ensure zero draft buttons exist.
  - Lock email or identifier if applicable, prefill existing avatar and details.

- [ ] **Step 2: Update `admin/roles/edit.blade.php`**
  - Ensure zero draft buttons exist.
  - Maintain identical card header with icon, border dashed, and permissions tree.

- [ ] **Step 3: Update `admin/clients/edit.blade.php`**
  - Align with `admin/clients/create.blade.php` (4-step layout with live preview card).
  - Lock `commercial_number` and `tax_number` as `readonly` (with hidden inputs).
  - Pre-fill client company info, primary admin, subscription status.
  - Remove draft buttons.

- [ ] **Step 4: Update `admin/coupons/edit.blade.php`**
  - Mirror `admin/coupons/create.blade.php`.
  - Lock coupon `code` as `readonly` text with font-monospace (since coupon codes must remain immutable).
  - Remove "توليد" (generate) button or disable it in edit mode.
  - Remove draft button.

- [ ] **Step 5: Update `admin/plans/edit.blade.php`**
  - Mirror `admin/plans/create.blade.php` (4 steps).
  - Lock plan `code` as `readonly` (with hidden input).
  - Remove draft button.

- [ ] **Step 6: Update `admin/settings/banks/edit.blade.php` and `domains/edit.blade.php`**
  - Remove draft buttons, prefill fields, ensure `@method('PUT')`.

---

### Task 7: Comprehensive Verification & Route/Blade Audit

**Files:**
- Audit: All modified files in Tenant and Admin.

- [ ] **Step 1: Check for any remaining draft buttons**
  - Run ripgrep for `draft-button` in all `edit.blade.php` files. Expected: 0 matches.
- [ ] **Step 2: Check for presence of `@method('PUT')` in all edit views**
  - Run ripgrep for `@method('PUT')` in all `edit.blade.php` files. Expected: Match in every edit file.
- [ ] **Step 3: Check for disabled vs readonly inputs**
  - Ensure every disabled input has an accompanying hidden input or uses `readonly` so FormRequests do not fail.
- [ ] **Step 4: Run Blade syntax check**
  - Run `php artisan view:cache` or test rendering to ensure zero Blade compilation errors.
