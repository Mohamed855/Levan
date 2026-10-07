# Project Conventions & Coding Standards

## Frontend Build Verification (Mandatory)
- After every update to any `.scss`, `.css`, or `.js` file, run `npm run build` before completing the task.

## Form Components & Validation (Mandatory)
- `FormRequest::rules()`, `messages()`, and `attributes()` are the only source of truth for form validation. Do not duplicate Laravel validation rules in page-specific JavaScript.
- Every reusable form control must expose the shared validation DOM contract (`data-form-field`, `data-field-name`, `data-form-control`, accessible error output) so it receives immediate validation on input/change/blur.
- The shared frontend validation endpoint must validate against the actual target FormRequest. Browser-only validation is supplementary UI behavior and never replaces final Laravel validation.
- Keep `x-filter.select` separate from form select components; it is a DataTable filter, not a submitted form field.

## DataTable Activity Statuses (Mandatory)
- Any index table that permits changing an activity state must render it with the reusable `js-table-status-toggle` switch, not an active/inactive text badge.
- The `الحالة` column must be immediately before the `الإجراءات` column.
- Reuse `resources/assets/js/shared/table-status-toggle.js`; do not introduce a per-page status-toggle AJAX handler.

## 1. Database Keys and Localization Mapping (Mandatory Invariant)
- **English-Only Database Keys & Identifiers**:
  - NEVER store Arabic letters, words, or phrases as keys, codes, slugs, or identifiers in any database column.
  - This applies unconditionally to Spatie permissions (`name` column in `permissions` table), roles (`name` in `roles`), statuses, setting keys, and configuration dictionaries.
  - Permission names MUST use English dot-notation structured as `module.action` or `entity.action` (e.g., `dashboard.main`, `users.view`, `users.create`, `users.edit`, `users.delete`, `plans.view`, etc.).
  - Roles MUST use English identifiers (e.g., `SuperAdmin`, `admin`, `manager`).
  - Statuses and enum keys MUST use lowercase English snake_case (e.g., `active`, `pending_payment`, `completed`).

- **Localization & Mapping Architecture**:
  - Arabic words and phrases belong exclusively in:
    1. Localization translation files (`lang/ar.json` or `lang/ar/permissions.php`).
    2. UI display layers (Blade views, labels, badges) via translation helpers: `__('permissions.' . $permission->name)` or `trans(...)`.
  - When rendering permissions in tables, matrices, or checkboxes, always fetch the English key from DB and translate it in the view.

- **Code Directives & Authorization Checks**:
  - Authorization checks in Blade and Controllers MUST use the English key:
    - **Correct**: `@can('dashboard.main')`, `@can('users.view')`, `$this->middleware('can:users.view')`, `$user->can('users.create')`
    - **PROHIBITED**: `@can('لوحة القيادة الرئيسية')`, `@can('المستخدمين')`, `$this->middleware('can:المستخدمين')`

## 2. Index Page Architecture & Layout Standards (Mandatory)
- **Top Section**:
  - Index pages MUST start directly with statistics cards (either `<div class="row g-4 mb-4">` or `<x-data-table.page table-id="...">` with `<x-slot:stats>`).
  - NEVER introduce a top heading banner (`<div class="d-flex justify-content-between ...">`, `<h1>`/`<h4>`, or a standalone "Add" button) above stats cards or filters.
- **DataTable Header Actions & Add Button**:
  - The "Add" button (`إضافة ...`) belongs EXCLUSIVELY inside the DataTable header toolbar next to the "الإجراءات" (Actions) dropdown, rendered via Yajra DataTable `buttons()`.
  - All standard index DataTables must implement:
    ```php
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }
    ```
  - `getActionsDropdownButton()` returns a collection button (`className => 'btn btn-export'`, `text => 'الإجراءات'`) with `copy`, `excel`, and `حذف المحدد` (calling `handleMassDelete()`).
  - `getAddButton()` returns a button (`className => 'btn btn-primary btn-add'`) guarded by `{module}.create` permission.

## 3. Mass Delete & Selection Contract (Mandatory)
- **Checkbox Column**:
  - Define checkbox column in `getColumns()`:
    `['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox']`
  - In `addCustomColumns()`:
    `->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')`
- **Table Event Delegation**:
  - In page scripts, bind `#select-all` and `.row-checkbox` event delegation on the table wrapper (`#table-id_wrapper`).
  - Uncheck `#select-all` on table `draw.dt`.
- **Model Resolution in `config/models.php`**:
  - Any model supporting mass delete via `confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'ModelName')` MUST be mapped in `config/models.php`.

## 4. Deletion Responses & Toast Notifications (Mandatory)
- Single deletions triggered by `<x-actions.delete>` submit a hidden HTML form (`#delete-form-{id}`) via `window.confirmDelete(id)`.
- Because form submissions are standard browser requests (not AJAX), returning a raw JSON response will render raw JSON text in the browser.
- All controller `destroy`, `forceDelete`, and `restore` methods MUST check:
  ```php
  if (request()->ajax() || request()->wantsJson()) {
      return response()->json(['success' => true, 'message' => '...']);
  }
  return redirect()->back()->with('success', '...');
  ```
- This ensures session flash messages trigger Toastr notifications automatically upon redirect.

## 5. Archive Views Layout Standards (Mandatory)
- Archive views MUST NOT contain any page title headers, breadcrumbs, or statistics cards (`<x-ui.stat-card>` / `<div class="row g-4 mb-4">`).
- Archive views consist strictly of:
  1. Optional error alerts.
  2. Optional filters (`<x-data-table.filters>`).
  3. The table card (`<div class="card"><div class="card-body">{!! $dataTable->table(...) !!}</div></div>`).
- In archive DataTables:
  - `getActionsDropdownButton()` provides `copy` and `excel`.
  - `getAddButton()` returns `[]`.

## 6. Golden Pattern for System Administrations (Mandatory)
- The Golden Pattern applies directly to every current and future SuperAdmin and Tenant administration; no administration is exempted for a later migration.
- Every administration must have a complete manifest covering: scope, route/middleware/guard, Controller, FormRequest, Policy, Action/Service, Model/Enum/Event/Job, DataTable or Blade UI, shared frontend contract, and tests.
- The mandatory flow is: `Controller → FormRequest → Policy → Action/Service → UI → Tests`.
- Use a Modular Monolith with explicit `Central` and `Tenant` boundaries. Tenant code must not access Central models directly; cross-boundary work must use an explicit central service/gateway and tenant context.
- Do not add a generic Repository layer around Eloquent models. Use Actions for single transactional business operations and Services for use-case orchestration.
- The canonical pattern is documented in `docs/architecture/golden-pattern.md`; its implementation and acceptance plan is `docs/system-unification-implementation-plan-ar.md`, stage 10.
