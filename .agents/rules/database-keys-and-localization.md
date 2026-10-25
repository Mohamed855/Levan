# Rule: English Database Keys & Localization Mapping

## Invariant
- NEVER store Arabic letters, words, or phrases as keys, codes, slugs, or identifiers in any database column.
- Spatie permissions (`name` column in `permissions` table), roles (`name` in `roles`), statuses, setting keys, and configuration dictionaries must strictly use English words/letters.
- For permissions, use English dot-notation: `module.action` or `entity.action` (e.g. `dashboard.main`, `users.view`, `users.create`, `plans.view`, etc.).
- UI text and Arabic labels belong exclusively in localization files (e.g., `lang/ar/permissions.php` or `lang/ar.json`) and must be translated dynamically using Laravel's translation helpers (`__('permissions.' . $permission->name)`).
- All authorization checks in code (`@can`, `@canany`, `$this->middleware('can:...')`, `$user->can(...)`) must strictly use English keys.
