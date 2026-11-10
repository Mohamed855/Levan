# First-login password change verification

Update: the historical wider-suite failures listed below have been resolved through test alignment. The current combined regression set passes 215 tests (7 skipped); 71 affected tests also pass in randomized order. See `docs/testing/auth-test-alignment.md` for changes and separately confirmed production findings.

The requirement is enabled when creating accounts whose initial credentials are emailed: central admin users, tenant staff and the tenant's first user. Existing accounts retain a false flag. Self-registration uses the password chosen by the user.

## Passing checks

- Focused feature/regression run: **47 passed, 310 assertions** (`FirstLoginPasswordChangeTest`, `AccountPasswordChangeTest`, `UsersAndProfilesDualReviewTest`, `PasswordComplexityPolicyTest`).
- Real tenant route test verifies mandatory redirect, password replacement, logout, cleared session identity and preserved `tenant_login_context`.
- Browser verification using the built assets: modal visible; no dismiss button; Escape, backdrop click and `Modal.hide()` blocked; password inputs usable; no browser errors.
- Production asset build exited 0; PHP syntax and Blade compilation passed. Build reports the existing empty `form-validation` entry warning.
- Independent read-only security/code review: no actionable findings.
- Applied only the two new additive migrations to local MySQL (`127.0.0.1`, database `etmam_levan`) and local tenant `test`. Both completed successfully. No existing account flags were backfilled.

## Wider-suite failures outside the changed flow

Latest authentication suite: **166 passed, 7 skipped, 2 failed**. The failures are:

- `OverviewAndSecurityDualReviewTest::test_tenant_dashboard_kpis_and_auction_activity_ranges`: its isolated schema lacks `auction_establishment_requests`.
- `RolesAndPermissionsDualReviewTest::test_tenant_grouped_permissions_orders_issuing_entities_before_assignments_and_after_dashboard`: expected dashboard permission section is absent during the combined run. Re-running this test alone passes (6 assertions), pointing to order-dependent test state; the specific state leak has not yet been isolated.

Additional checks also exposed these failures in unchanged OTP / two-factor paths:

- `TenantAuthenticationContractTest::test_two_factor_setup_uses_the_shared_validation_contract`: the test expects the generic `wizard-drafts.validate-step` URL, while the view uses its dedicated `tenants.two-factor.validate` endpoint.
- `TenantPasswordResetOtpFeatureTest::test_requesting_otp_succeeds_and_transitions_to_otp_step`: OTP session creation and rendering succeed; the final assertion expects the user's email in the OTP page, but that page does not render it. The previous report incorrectly described this as missing session state.
- `TenantPasswordResetOtpFeatureTest::test_verifying_invalid_otp_fails_and_increments_attempts`: the manually constructed session omits required `tenant_id`; tenant-context validation rejects and clears it before checking attempts.
- `TenantPasswordResetOtpFeatureTest::test_resending_otp_updates_timer_and_respects_max_resends`: the manually constructed session omits required `tenant_id`, producing 422.
- `TenantPasswordResetOtpFeatureTest::test_verifying_valid_otp_marks_session_verified_and_redirects_to_reset_password`: the manually constructed session omits required `tenant_id` and is rejected as invalid.
- `TenantPasswordResetOtpFeatureTest::test_resetting_password_with_verified_otp_updates_password_and_redirects_to_login`: the manually constructed session omits required `tenant_id` and is rejected as invalid.
- `ScopedPasswordResetAndFaqAuthorizationTest::test_verified_tenant_otp_reset_keeps_using_the_verified_email`: the manually constructed session omits `tenant_id` and `user_id`, and the test does not establish the tenant context required by the reset controller.

These were reported rather than changing unrelated features. The full project suite was not run; some tests depend on existing local tenant data.
