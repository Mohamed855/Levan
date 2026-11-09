# Auction establishment workflow verification — 2026-11-09

## Delivered behavior

| Requirement | Implementation and verification |
| --- | --- |
| Request management follows current application conventions | Tenant menu, permission group, statistics, filters, server-side DataTable, typed private attachments; view links to auction details, no request show route. HTTP index and JSON table verified. |
| Preserve the user's creation stepper | Existing six-step views and page JavaScript retained. Shared draft uploads support the per-auction update route, final request restores uploads, successful PUT removes the draft. Cross-auction draft reuse and uploads after approval are rejected. |
| Native form generation after request save | Save action creates an immutable generation snapshot; details polling dispatches the background worker, displays progress, and enables the download. Auction creation alone cannot create this form. |
| Approval number moves to request approval | Shared Bootstrap modal on auction details requires a nonblank number; leading zeroes survive. Closing copies the approved number and cannot supply an alternative. |
| Approved requests cannot be edited | HTTP FormRequest and locked action both reject edits. Approved auction property membership/assignment cannot change; closing revalidates snapshot property membership before writing. |
| Closing requires approval | Pending/missing request cannot be closed. Saving a valid closing refreshes existing reports and only the closing form. |
| All linked properties sold closes the auction | Nonempty actual pivot results determine closed status, including legacy all-sold auctions. Closed auction/closing/marketing mutations are rejected; both forms can still be regenerated. |
| Automatic lifecycle | Pending, approved, closed replace date/manual states; old activation endpoint refuses mutation. Filters, badges, statistics and dashboard use the new states. |
| Separate marketing completeness | Complete/incomplete derives from configured channels and required persisted fields, including safe links and consistent WhatsApp totals. Optional evidence stays optional. |
| Closed marketing restrictions | Create, update, delete, batch and auction-level campaign removal paths reject closed auctions. |
| Shared details permissions | Request-only and closing viewers can access auction details; auction listing/mutations still require their own permissions. |
| Robust document delivery | Tenant restoration, private files, stale job rejection, immutable snapshots, generic public errors and retained last good PDFs covered. |

## Evidence

- Focused PHP suite: **100 tests / 538 assertions passed**, including a real Chromium render of both forms. See `tmp/auction-workflow-verified.log`.
- Shared draft frontend safety: **3 Node tests passed**.
- `npm run build`: passed (27.54 seconds).
- PHP syntax: **93 changed/new PHP files passed**; `git diff --check` passed.
- Browser checks on real HTTP-rendered HTML using built assets: six-step initialization, pending approval modal with required approval number, document status polling/download enablement, and approved view without approval modal passed. HTTP tests cover the backend; the browser replay substitutes network responses rather than changing live records.
- Native PDF job generated establishment **6 pages** and closing **1 landscape page** from request/closing fixtures. All seven pages inspected after Poppler rendering. pypdf verified real text, approval `000900`, deed `000123456789`, and exact award value `1,350,000.25`.
- Local environment inspected: central and tenant hosts `127.0.0.1`, tenant `test`, database `etmam_levan_test`. All three additive workflow migrations were already installed; both new tables and all five request permissions verified present. Tests use the separate `levan_report_ci_*` schemas.
- Independent review identified approved membership drift and request-only detail access. Both fixed with regression tests.

## Broader-suite limits

The wider auction suite ran **262 tests / 2,088 assertions**, with **6 errors and 7 failures**, so the full suite is not green. Remaining failures concern template preset page counts, duplicate-channel report aggregation, older native marketing-input expectations, old campaign HTTP fixtures missing currently required fields, a promotional card source assertion, and tests hardcoding tenant `test` which is absent from the isolated central schema. The relevant workflow-focused tests above pass. Details: `tmp/auction-workflow-broad-final.log`.

The earlier native template work has known typography differences from the original PDFs. This integration verifies real native PDF generation and data fidelity; it does **not** establish a 100% pixel-identical match to the supplied references.

No commit, push, deployment or destructive database operation was performed. Existing user edits, including the establishment stepper, remain in place.
