# Trial registration

The trial form shares the company/owner inputs and validation contract with paid checkout. It hashes the chosen password before storing the pending OTP session. A verified OTP stores the full central trial request; no bill, payment or tenant is created before approval. A pending legacy request can be completed by verifying the same email, preserving its record.

Approval claims the request under a central row lock, provisions the tenant and remembers its ID before migrations, creates the core owner license and a planless subscription, then marks the request approved. Retrying an interrupted provisioning operation reuses the same tenant, owner and subscription. The seven-day clock starts only after successful provisioning. No approval email is sent; the login link is shown in the admin request details.

`config/trial.php` defines the minimum trial resource snapshot: one owner, one property, one active auction and 1 GB storage. Additional users are blocked in the policy, the management service and the public registration action. Additional licenses cannot be purchased during the trial. Expiration uses the exact subscription `ends_at`, not a date-only midnight value.

Apply the additive migration:

```sh
php artisan migrate --path=database/migrations/admin/2026_10_26_000100_expand_trial_requests_for_registration.php
```

Verification: 25 focused tests passed (167 assertions), including paid checkout and report generation regressions. The trial flow tests provision a dedicated temporary database and clean up their own records. They cover OTP failure/expiry/replay, incomplete legacy requests, upgrading a legacy request after verification, interrupted provisioning/retry, repeated approval, core owner/license, actual login, dashboard and assignments access, disabled user creation, and expiry.

An additional smoke check against the running localhost server used real cookies and CSRF tokens: form/OTP completed, the tenant login and authenticated dashboard/assignments returned HTTP 200, user creation returned HTTP 403, and the user count remained one. The temporary tenant and database were removed afterward.

The existing `PublicRequestsGoldenPatternTest` still has two unrelated source-text assertions expecting raw date inputs in unchanged contact/assignment pages that now use the shared `<x-filter.date>` component; these were not changed by this feature.
