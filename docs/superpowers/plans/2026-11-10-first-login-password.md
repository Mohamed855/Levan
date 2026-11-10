# First login password change implementation plan

Goal: Require a new password on first login, using the existing modal, password form, validation and password update methods, then log out.

- [x] Add regression tests for mandatory access control, validation failure, password replacement, logout and subsequent login for both guards.
- [x] Persist `must_change_password` with a false default; set it explicitly only when creating accounts whose initial password is emailed. Preserve all existing accounts.
- [x] Enforce the requirement after authentication, allowing only the password form, its validation and submission, and logout. Keep the tenant password routes accessible before subscription/license checks.
- [x] Render the existing password form in the existing modal without dismiss or back controls. Ensure the shared modal script cannot insert a close button.
- [x] Clear the requirement through existing password update methods, end the session after the mandatory change and preserve tenant login context.
- [x] Run focused tests, PHP syntax checks, Blade compilation and the asset build; review the final diff for scope and security.

Decisions: Apply to emailed initial credentials in both panels. Normal later password changes retain their current behavior. Use dedicated route middleware; do not change global authentication middleware. Migrations are additive with a false default and do not backfill existing accounts. Verified the current environment is local, MySQL host 127.0.0.1, with one tenant (`test`); applied only these two migration files locally. Work in the current checkout to preserve its existing uncommitted changes.

Verification: A read-only reviewer found no actionable defects. Browser checks confirmed the modal opens, has no dismiss control, rejects Escape/backdrop/programmatic hide and accepts input. PHP syntax, Blade compilation and the production asset build passed. The broader authentication run exposed unrelated dashboard-schema, permission-order and OTP test failures; focused feature regression tests are recorded separately.
