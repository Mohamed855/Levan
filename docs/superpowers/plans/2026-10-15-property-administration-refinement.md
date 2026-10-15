# Property Administration Refinement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Tenant property administration data-safe, fully functional, and visually consistent with the golden assignment wizard pattern.

**Architecture:** Keep the existing Controller → FormRequest → Action → UI flow. Add small property-unit enums and a tenant migration for persisted keys; keep dynamic wizard state in the shared draft subsystem but repair the property UI contract that restores it.

**Tech Stack:** Laravel 11, Blade, Yajra DataTables, Select2, Bootstrap 5, vanilla JavaScript, tenant migrations.

**Spec:** `docs/superpowers/specs/2026-10-15-property-administration-refinement-design.md`

## Global Constraints

- Tenant data stays inside the tenant connection and existing policy/FormRequest boundaries.
- Database keys are English-only; Arabic is display-only through enums.
- FormRequests remain the only validation source of truth.
- Dynamic controls expose `data-form-field`, `data-field-name`, and `data-form-control` so shared validation can render errors.
- Run `npm run build` after JS/SCSS changes.

## Review Focus

- Draft with annotated base64 image and polygon must restore both without requiring a new upload.
- A dynamically added owner with invalid fields must display its own shared validation error styling.
- New and restored dynamic Select2 controls must submit English enum values, never Arabic labels.
- Legacy Arabic unit values must migrate deterministically without changing unrelated records.
- Index filters must send their values on the actual DataTable AJAX request and narrow results correctly.

---

### Task 1: Repair and verify property index filters

**Files:**
- Modify: `resources/views/tenants/properties/index.blade.php`
- Modify: `app/DataTables/Tenants/Properties/PropertiesDataTable.php`
- Test: `tests/Feature/PropertyManagementFeatureTest.php`

**Interfaces:**
- Produces filter request keys `assignment_status`, `assignment_id`, `type`, `data_status`, and `city` consumed by `applyCustomFilters()`.

- [ ] Write a feature test that requests DataTables with each filter and asserts only matching property records are returned.
- [ ] Run the focused test and verify it fails because filter data is not wired correctly.
- [ ] Make the filter target and request contract match the shared filter controller; preserve Select2-only filter controls.
- [ ] Re-run the focused test and verify it passes.

### Task 2: Normalize validation, units, and boundary contracts

**Files:**
- Create: `app/Enums/Tenants/Properties/PropertyUnitType.php`
- Create: `app/Enums/Tenants/Properties/PropertyUnitUsage.php`
- Create: `database/migrations/tenant/*_normalize_property_unit_type_and_usage_keys.php`
- Modify: `app/Enums/Tenants/Properties/PropertyBoundaryDirection.php`
- Modify: `app/Http/Requests/Tenants/Properties/StorePropertyRequest.php`
- Modify: `app/Http/Requests/Tenants/Properties/UpdatePropertyRequest.php`
- Modify: `app/Models/Tenants/Properties/PropertyUnit.php`
- Test: `tests/Unit/TenantPropertyStatusContractTest.php`
- Test: `tests/Feature/PropertyManagementFeatureTest.php`

**Interfaces:**
- Produces English unit values `villa|apartment|shop|garden|other` and `residential|commercial|other`.
- Produces boundary values including `northeast|northwest|southeast|southwest`.

- [ ] Write failing tests for accepted English enum keys, rejected Arabic labels, optional `google_maps_url`, numeric identifiers, and diagonal boundaries.
- [ ] Run the focused tests and verify failure.
- [ ] Add the enums, casts, FormRequest rules, labels, and tenant migration that maps legacy values safely.
- [ ] Re-run the focused tests and verify pass.

### Task 3: Repair dynamic wizard controls and draft restoration

**Files:**
- Modify: `resources/views/tenants/properties/create.blade.php`
- Modify: `resources/views/tenants/properties/_boundaries-table.blade.php`
- Modify: `resources/views/tenants/properties/_location-map-card.blade.php`
- Modify: `resources/assets/js/tenant/properties/create.js`
- Test: `tests/Feature/PropertyManagementFeatureTest.php`

**Interfaces:**
- Consumes unit/boundary enum options emitted by Blade into `window` configuration.
- Restores `aerial_image`, `boundary_geojson`, boundaries, owners, and units through `wizardDraftRestored`.

- [ ] Write failing DOM/feature tests for initial four directions, optional additions, numeric controls, draft image/GeoJSON fields, and dynamic validation hooks.
- [ ] Run the focused test and verify failure.
- [ ] Convert boundary type and unit type/current-use controls to non-searchable Select2, reinitialize them after add/restore, and use shared data-table classes for units.
- [ ] Give every dynamic owner field the shared validation field/error contract.
- [ ] Repair aerial image and polygon draft serialization/restoration without bypassing tenant-scoped draft ownership.
- [ ] Re-run focused tests and verify pass.

### Task 4: Align additional attachments and review presentation

**Files:**
- Modify: `resources/views/tenants/properties/create.blade.php`
- Modify: `resources/assets/js/tenant/properties/create.js`
- Modify: `resources/assets/scss/pages/assignment-create.scss` or a focused property wizard stylesheet if one exists
- Test: `tests/Feature/PropertyManagementFeatureTest.php`

**Interfaces:**
- Uses the assignment-style counted box contract for `property_images` and `additional_documents` in step four and review.

- [ ] Write failing DOM tests for the two counted boxes, zero-file states, and aerial-review download control.
- [ ] Run the focused test and verify failure.
- [ ] Implement escaped, removable file cards and matching review boxes; render the annotated aerial preview and a hover download action.
- [ ] Run focused tests and `npm run build`; verify both pass.

### Task 5: End-to-end review

**Files:**
- Test: `tests/Feature/PropertyManagementFeatureTest.php`
- Test: `tests/Unit/TenantPropertyStatusContractTest.php`

- [ ] Run the complete property test group, PHP syntax checks, `npm run build`, and `git diff --check`.
- [ ] Review tenant scoping, FormRequest coverage, output escaping, and migration mappings against the spec.
