# Admin Users Hardening and Standardization Plan

## Current baseline

- The project-wide filter migration is now documented separately in `implementation_plan.md`.
- Admin Users already uses that contract: its filter card targets `#users-table`, declares `status` and `role` request keys, and its duplicate `filter.js` was removed.
- Keep the global filter migration and Admin Users hardening as separate commits. The Admin Users work must not modify the shared filter controller unless an Admin Users integration test proves a defect.

## Phase 1 — Secure destructive operations

1. Add an Admin Users-specific bulk-delete route and controller action.
   - Require `users.delete`.
   - Validate selected IDs.
   - Reject Super Admin IDs on the server.
   - Delegate deletion to `UserService`, including image cleanup.

2. Harden archive operations.
   - Add authorization to archive index, restore, and force-delete actions.
   - Prevent restore or force-delete of Super Admin.
   - Delete image files when force-deleting a user.

3. Secure uniqueness endpoints.
   - Apply the relevant create/edit authorization.
   - Validate name, email, and phone request values.
   - Make the email check ignore the current user during edit.

## Phase 2 — Backend and lifecycle consistency

1. Route single delete, bulk delete, and force-delete through `UserService`.
2. Use `findOrFail`/route-model binding in the profile action and eager-load its role.
3. Eager-load roles in active and archived DataTables to eliminate per-row role queries.
4. Remove the unused statistics service call from the index controller and use one source for dashboard counts.
5. Change the activity log name to the English database key `admin_users`, translate it in the UI, and migrate existing matching log records where appropriate.
6. Define a single avatar field: CRUD and profile use `image`. Remove non-functional profile/background image forms with empty actions, or implement them completely with routes, validation, service methods, and tests.

## Phase 3 — Admin Users frontend cleanup

1. Verify the shared filter controller sends only `status` and `role` to `#users-table`, and a reset causes one reload.
2. Consolidate create/edit validation in the Admin Users module JavaScript.
   - Remove duplicate inline validation.
   - Remove rules for absent fields (`employee_number`, `branch_id`).
   - Align browser validation with server rules for name, phone, password, and image.
3. Preserve the reusable `js-table-status-toggle`; the status column must remain immediately before actions.

## Phase 4 — Verification

1. Extend the Admin Users feature test for:
   - authorization of bulk delete, archive restore/force-delete, and uniqueness endpoints;
   - Super Admin protection on every destructive/status operation;
   - stored-image cleanup;
   - current-user email uniqueness;
   - DataTable `status`/`role` request parameters.
2. Run focused PHPUnit tests, `npm run build` after JavaScript changes, and `git diff --check`.
3. Review the final scope: only Admin Users, its archive, activity-log localization/migration, direct shared helpers, and its tests.
