# Project Conventions & Coding Standards

## Frontend Build Verification (Mandatory)
- After every update to any `.scss`, `.css`, or `.js` file, run `npm run build` before completing the task.

## Form Components & Validation (Mandatory)
- `FormRequest::rules()`, `messages()`, and `attributes()` are the only source of truth for form validation. Do not duplicate Laravel validation rules in page-specific JavaScript.
- Every reusable form control must expose the shared validation DOM contract (`data-form-field`, `data-field-name`, `data-form-control`, accessible error output) so it receives immediate validation on input/change/blur.
- The shared frontend validation endpoint must validate against the actual target FormRequest. Browser-only validation is supplementary UI behavior and never replaces final Laravel validation.
- Keep `x-filter.select` separate from form select components; it is a DataTable filter, not a submitted form field.
- **Create Forms & Drafts Contract (Mandatory)**:
  - ALL create forms (single-card forms and wizards alike) MUST include a "حفظ كمسودة" (Save as Draft) button alongside primary form action buttons.
  - The draft button (`<x-form.wizard.draft-button />`) and the next/save button (`<x-form.wizard.next-button />` or `<x-form.wizard.save-button>` / submit) MUST be enclosed together inside `<div class="d-flex gap-2">` to maintain uniform spacing and flex alignment across all create views.
  - All create forms must be wired to the drafts subsystem/table: clicking "حفظ كمسودة" saves current inputs and uploaded files progressively without enforcing full mandatory creation validation, restores saved draft data upon reopening the form, and automatically discards/purges the draft upon successful record creation.
  - **Drafts Table Multi-Tenancy**: The drafts table for Tenant administrations MUST reside in the **tenant database** (tenant migrations/connection) so tenant forms store and isolate drafts directly within each tenant's database. Central/SuperAdmin forms store drafts in the central database drafts table.

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
- The canonical pattern and its file-by-file implementation checklist are documented in `docs/architecture/golden-pattern.md`; administration compliance is tracked in `docs/baselines/golden-pattern-manifest.md`.

## 7. Visual Layout, UX & Design Architecture Standards (Mandatory Reference)
- **Assignments & Properties as Canonical References**: All multi-step wizard administrations and show/index views must strictly mirror the visual architecture, component contracts, and UX patterns established in `tenants/assignments` and `tenants/properties`.
- **Index View Layout**:
  - Statistics cards must use **5 uniform cards** with `col="col-sm-6 col-xl"` representing the entity lifecycle (Total `primary`, Active/Ready `success`, Completed/Under Review `info`, Unlinked/Pending `warning`, Needs Review/Suspended `danger`).
  - Table image column must render as `rounded-circle` with fixed dimensions.
  - Entity codes must use `.copyable-code-wrapper` with single-click clipboard copy and Toastr notification.
- **Wizard Create & Edit Forms**:
  - Stepper indicators must use clean, concise titles without subtitles (`['id' => '...', 'title' => '...']`).
  - Form steps must use an open full-width grid (`<div class="row g-4">` with `col-md-6` / `col-12`). NEVER introduce artificial sidebar columns (e.g. `col-lg-4` percentage widgets or checklist cards) inside input steps.
  - Dynamic subtables (boundaries, owners, units, child items) must use `.assignment-properties-table-card` with an explicit card-header, title, dynamic count, primary add button (`btn-sm btn-primary`), and uniform row delete buttons (`btn-icon btn-sm btn-label-danger`).
  - The final review step MUST use the canonical split layout:
    - **Right column (`col-lg-9`)**: Review cards using `<x-form.wizard.review-card>` with quick edit buttons (`data-edit-step`) jumping directly to the corresponding step via `bsStepper.to(step)`.
    - **Left column (`col-lg-3`)**: Live entity preview card `<article class="card assignment-preview h-100">` with top image (`.assignment-preview__image` aspect-ratio 4:3), entity name, and description.
  - **Wizard Actions & Draft Button Grouping**: In all wizard create steps, the draft button (`<x-form.wizard.draft-button />`) and the forward action (`<x-form.wizard.next-button />` or `<x-form.wizard.save-button>`) must be wrapped together inside `<div class="d-flex gap-2">`.
- **Show View Layout**:
  - Must use the **Two-Column Asymmetric Layout**:
    - **Left column (`col-xl-4 col-lg-5 col-md-5 mb-4`)**: Profile card (centered 100x100px rounded image, name, category badge, horizontal stat counters, key-value info list) + Attachments card underneath rendering a clean file list with `ti ti-download` action buttons.
    - **Right column (`col-xl-8 col-lg-7 col-md-7`)**: Dedicated strictly to child subtables and related records (1:M tables, auctions, history) using clean `<div class="card mb-4"><div class="table-responsive"><table class="table table-hover">...</table></div></div>`.
- **Unified Typography Scale Bands**:
  - **Band 1 (`11px - 13px -> 12px` / `0.75rem`)**: Small helper texts, metadata dates, badges, tooltips (`xs`, `sm`, `.form-text`).
  - **Band 2 (`14px - 16px -> 15px` / `0.9375rem`)**: Body texts, inputs, standard buttons, table content, top-level and submenu links.
  - **Band 3 (`17px - 24px -> 18px` / `1.125rem`)**: Card headers (`h5`), sub-headings (`h4`), card descriptive titles (`.text-xl`).
  - **Band 4 (`25px - 28px -> 24px` / `1.5rem`)**: Page titles and modal headers (`h3`).
  - **Band 5 (`> 28px`)**: Auth/welcome titles (`h2: 38px`), landing titles (`h1: 46px`).
  - **Icons Exemption**: Icon dimensions are strictly exempt from font scale reductions (`.menu-icon` 20px / `1.25rem`, Tabler/FA icons).
- **Sidebar & Viewport Scrollbar Contract**:
  - The sidebar (`#layout-menu`) must never display a scrollbar (`scrollbar-width: none; -ms-overflow-style: none; display: none;`), while preserving smooth mouse wheel navigation.
  - Viewport scrollbar must be strictly aligned to the **left** in RTL via `:root[dir="rtl"], html[dir="rtl"] { direction: rtl !important; }`.
  - Submenu items (`.menu-sub .menu-item`) must maintain a uniform height of `48px`, seamless concave scoops, and a vertical gap (`margin: 0.35rem 0 !important;`) preventing active/hover pill collision.
- **Brand Identity & Color Architecture**:
  - System brand is LOT («لوت LOT»). Primary color is `#1D4F44` and Secondary is `#f5efe6`.

