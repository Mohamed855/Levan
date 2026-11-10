# Repository Guidelines (`AGENTS.md`)

## Tech Stack
- **Backend:** PHP `^8.2`, Laravel `^12.0`, `stancl/tenancy` `^3.10` (dual-database central Admin + isolated Tenant DBs), `spatie/laravel-permission` `^8.0`, `spatie/laravel-activitylog` `^5.0`, `yajra/laravel-datatables` `^12.0`, `maatwebsite/excel` `^4.0`.
- **Frontend:** Blade anonymous components (`resources/views/components/shared`), Bootstrap 5 RTL, Tabler Icons (`ti ti-*`), jQuery, Select2, SweetAlert2, Toastr, Vite (`vite.config.js` auto-discovers `@vite(...)` directives in `resources/views/**/*.blade.php`).
- **Helpers:** Auto-loaded `app/Helpers/Helpers.php`, `app/Helpers/TenantHelper.php`, and `app/Helpers/DateHelper.php` (`system_date()`, `system_datetime()`, `parse_system_date()`).

## Standard Architecture ("Golden Pattern")

### 1. DataTable Modules (Admin & Tenants)
All tabular management/listing pages MUST follow the unified DataTable architecture instead of calling `DataTables::of()` directly inside controllers:
- **DataTable Class:** Place in `app/DataTables/{Admin|Tenants}/...` extending `App\DataTables\Abstract\AbstractDataTable`.
  - Implement `getTableId()`, `resource()`, `getColumns()`, `addCustomColumns()`, `rawColumns()`, `applyCustomFilters()`, `getSearchableColumns()`, and `buttons()`.
  - Rely on `AbstractDataTable::applyDefaultDateFilters()` for `created_from`/`created_to`/`date_from`/`date_to`/`deleted_from`/`deleted_to` instead of duplicating date range logic.
  - Format dates with `$this->formatDate(...)` (`d-m-Y`) or `$this->formatDateTime(...)` (`d-m-Y h:i A`), and render copyable identifiers via `$this->copyableCode(...)`.
- **Thin Controller:** Inject the `FormRequest`, the `*DataTable` class, and the domain `Service`/`Action`, returning `$dataTable->render('<panel>.<module>.index', [...])`. Never build DataTable HTML strings or raw Eloquent filter chains inside controllers.
- **Service / Action Layer:** Place business logic, statistics queries, export preparation, and shared presentation helpers in `app/Services/{Admin|Tenants|Shared}/...` or `app/Actions/...`.
- **Action Partials:** Render row actions via a dedicated `<panel>/<module>/action.blade.php` view using `<x-actions.row>` and `<x-actions.show>`, `<x-actions.edit>`, `<x-actions.delete>` (from `resources/views/components/shared/actions/`).

### 2. Blade Views & Components
Shared anonymous components are registered in `app/Providers/ViewServiceProvider.php` from `resources/views/components/shared`:
- **Index & Archive Pages:** Wrap in `<x-data-table.page table-id="...">` (or `<x-data-table.archive-page>`) with slots `<x-slot:stats>`, `<x-slot:filters>`, and `<x-slot:table>`.
- **Stats & Empty States:** Use `<x-ui.stat-card>` and `<x-ui.empty-state>` instead of custom card HTML or inline `@php` calculations in views.
- **Filters:** Use `<x-data-table.filters table="#...">` with `<x-filter.select>`, `<x-filter.date>`, and `<x-filter.input>`.
- **Shared Partials:** Place partials reused across Admin and Tenant under `resources/views/shared/`.

### 3. JavaScript & CSS Conventions
- `resources/views/components/shared/layout/scripts.blade.php` automatically loads shared DataTable infrastructure:
  - `datatable-filter-selects.js` (auto-initializes `.select2`, attaches filters on `preXhr.dt`, handles reset)
  - `datatable-page-defaults.js` (delegated `#select-all`, `.copyable-code-wrapper` copy, `executeMassDelete`, archive restore/force-delete)
  - `table-cell-clamp.js` (single-line cell clamping)
  - `datatable-feedback.js`, `datatable-responsive.js`, `datatable-primary-actions.js`
- Page-specific JS files should ONLY bind module-specific interactions (`init.dt`, `draw.dt`, modals, custom exports). Do NOT duplicate DataTable initialization, `dom`, `language`, `Select2` setup, or text-clamping logic in page JS.

### 4. Multi-Tenancy, Security & Code Style
- Always use `declare(strict_types=1);` in new PHP classes with explicit parameter and return types.
- Enforce authorization via middleware, `FormRequest::authorize()`, and Policies/Gates; use `tenant_route_name(...)` for tenant URLs and keep central/tenant databases strictly isolated.
- Never hardcode secrets or render unsanitized user-controlled strings in HTML columns (`e(...)`).

## Verification Commands
- **Tests:** `php artisan test` (or targeted `php artisan test tests/Feature/...`)
- **Code Style:** `./vendor/bin/pint --test`
- **Frontend Build:** `npm run build`
