# Authentication test alignment — 2026-11-10

The reported test failures were reproduced and aligned with the current system. Production behavior was not changed to satisfy the tests.

## Corrections

- Dashboard fixtures now include establishment requests, approval timestamps/numbers and the auction/property fields used by current queries. Assertions cover approved, pending, sold-out, deleted and older auctions, exact KPI totals and all four activity ranges.
- Permission ordering fixtures use scoped `forceCreate()`. Earlier tests build a smaller `permissions` schema without `section`; Eloquent caches guardable columns by model class across rebuilt applications, which previously discarded later fixtures' `section` values. Existing ordering and tenant-isolation assertions remain intact.
- Password-reset tests now use isolated SQLite databases and fake mail. Sessions are created through the real password-reset request/verification endpoints. Assertions check tenant/user identity and mail recipient instead of expecting an email address that the current OTP view does not display.
- The verified-email protection test now supplies a real initialized tenant context plus the verified user's ID. Its submitted-email tampering assertion remains intact.
- The two-factor contract now expects the dedicated `tenants.two-factor.validate` endpoint used by the view.
- Added password-reset coverage for a mismatched tenant, an expired OTP within a valid session, and rejection after the verification session expires.

## Verification

Combined authentication, password, email-branding and relevant contract tests: **215 passed, 7 skipped, 1519 assertions**.

Randomized affected test classes, including ActivityLogs followed/intermixed with Roles: **71 passed, 455 assertions**, seed **20261110**.

All five changed test files pass PHP syntax and whitespace checks. The full repository suite was not run; this verification targets the reported failures and their surrounding authentication paths.

## Confirmed system issues

### Expired verification sessions can still request another OTP

`PasswordResetLinkController::resendOtp()` checks tenant binding, OTP presence and resend count, but omits the `session_expires_at` check enforced by `verifyOtp()`.

Reproduced with isolated SQLite and fake mail:

1. Request a password-reset OTP.
2. Advance time by 16 minutes, beyond the 15-minute verification session.
3. Resend returns HTTP 200 and queues a second OTP.
4. Submitting that replacement OTP redirects to the request page and clears the expired session.

This sends an unusable replacement code; verification still rejects the expired session. The diagnostic probe deliberately expects HTTP 422 and fails at that assertion after confirming the second mail and rejected verification. It is kept outside the normal test suite because the user requested reporting genuine production issues separately.

Reproduce:

```powershell
php artisan test scratch/ExpiredOtpResendProbeTest.php --filter=expired_verification_session_should_not_send
```

### Email expiry wording does not match the actual OTP lifetime

`resources/views/emails/auth/otp.blade.php:23` states that the OTP lasts **10 minutes**. Both the password-reset controller and the trial-registration controller issue and resend codes with **2-minute** expiry timestamps. The expiry wording should be passed from the issuing flow or otherwise match its actual lifetime.
