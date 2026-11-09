# Generate auction documents during seeding

The user explicitly replaces deferred demo-file rendering with completed files during seeding. Keep web save/polling behavior and opt-in configuration unchanged. Preserve unrelated user edits.

- [x] Add a seed-only synchronous generator using the existing report/form jobs and renderer, after scenario transactions finish. Generate only applicable forms; validate ready states and private files.
- [x] Wire both auction demo seeders, including existing/renamed scenarios and the legacy mixed closing. Reuse valid ready files and complete pending/failed/missing files without duplicating valid editions.
- [x] Update seeder regressions for ready and published files, no background jobs, repeat stability, failure visibility and missing-file recovery. Verify actual Chromium rendering on an isolated scenario as well as fast renderer-boundary tests.
- [x] Update seeding documentation; run syntax, formatting and focused regressions. Never run a fresh seed on shared business data.

## Verification and decisions

- Final isolated test run: **20 tests / 340 assertions passed**, including real Chromium rendering. Log: `tmp/seed-completed-documents-verification.log`.
- Real PDFs: outputs 8 pages, report 20 pages, establishment 6 pages, closing 1 landscape page. PDF text extraction checked leading-zero approval/deed numbers and exact award values; representative page PNGs inspected.
- Actual report rendering exposed overlong seeded titles. Compact titles retain full scenario descriptions, and base demo aliases avoid duplicate auctions. Existing unchanged WF default titles are migrated narrowly, including matching title metadata in synthetic closing report snapshots; approved request data is preserved exactly and tester-renamed titles remain unchanged.
- Independent review identified legacy queued-title recovery and corrupt-file reuse. Both reproduced with failing tests and fixed; reused PDFs are checked for header/EOF via bounded stream reads.
- Changed PHP syntax and scoped formatting/whitespace checks passed. No web generation flow, unrelated user changes, server deployment, or shared database reset was performed.
