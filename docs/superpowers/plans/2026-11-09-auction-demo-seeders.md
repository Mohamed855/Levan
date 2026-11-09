# Auction demo seeder updates

Authorized scope: update demo seeders for the new auction lifecycle and enable explicit opt-in on any APP_ENV, defaulting off. Preserve unrelated user changes. Do not run migrate:fresh or the destructive TestTenantSeeder against shared/local/server business databases.

-   [x] Replace environment checks in central/demo seeder entry points with config-backed TEST_SEEDERS_ENABLED, false in config, .env.example and .env. Respect configuration caching for the test tenant database and credentials.
-   [x] Keep normal tenant bootstrap free of demo data. Seed new scenarios only through the opt-in demo entry point.
-   [x] Preserve existing property/type/category coverage. Add named workflow scenarios: no request, under review, approved, partially closed, all unsold, fully closed, complete/incomplete marketing, and empty auction.
-   [x] Use real save/approval/closing actions, valid request data, private demo attachments and validated marketing campaigns. Queue generation snapshots for the details screen; avoid synchronous Chromium rendering during fresh seed.
-   [x] Verify opt-in behavior independently of APP_ENV and all lifecycle/completeness states on isolated CI schemas, repeat-run stability and linked document generation. Existing lifecycle guards are unchanged.
-   [x] Independent review, scoped tests/lint and server toggle documentation.

Evidence: 10 seeder tests / 183 assertions passed (`tmp/auction-seeders-focused.log`), 8 PHP files linted, scoped diff whitespace check passed. Independent review findings fixed with red/green tests: effective DB_URL credentials/database, case-insensitive central database exclusion, and stable scenario identity after renaming both populated and empty auctions.

## Server access-denied follow-up

Reported MySQL 1044 on unconditional CREATE DATABASE for the local test-database name. The effective MySQL user is already copied into tenant metadata. Add a dedicated temporary connection preflight before any central record changes: reuse accessible pre-created databases without CREATE permission, create only on MySQL unknown-database error 1049, and give actionable hosting-panel/grant/name instructions on failures. Preserve central connection/tenancy and always purge the probe. Verify all branches with mocked PDO failures; do not run a destructive seeder.

Completed: private preflight runs before central metadata writes, skips CREATE for accessible databases, retries after creation only for 1049, and handles permission/connectivity errors with hosting instructions. Probe strips central URL/read/write overrides, uses a separate connection name and is purged in finally. User specified server database `etec_lot_test`; deployment guide updated. Final verification: 15 tests / 229 assertions passed, PHP lint and scoped whitespace checks passed. Server grants were not changed or verified remotely.

Wider regression run: 111 tests / 667 assertions, with 6 errors and 3 failures. Eight cases in old establishment workflow fixtures now violate a concurrently updated positive-area rule; one existing closing DataTable structure expectation also fails. Those production and test files are outside this seeder change and were preserved. See `tmp/auction-seeders-verification.log`. No fresh operation was executed against business data.
