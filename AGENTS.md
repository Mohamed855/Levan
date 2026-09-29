# Project Conventions & Coding Standards

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
