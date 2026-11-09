# Auction demo seeder updates

Authorized scope: update demo seeders for the new auction lifecycle and enable explicit opt-in on any APP_ENV, defaulting off. Preserve unrelated user changes. Do not run migrate:fresh or the destructive TestTenantSeeder against shared/local/server business databases.

- [ ] Replace environment checks in central/demo seeder entry points with config-backed TEST_SEEDERS_ENABLED, false in config, .env.example and .env. Respect configuration caching for the test tenant database and credentials.
- [ ] Keep normal tenant bootstrap free of demo data. Seed new scenarios only through the opt-in demo entry point.
- [ ] Preserve existing property/type/category coverage. Add named workflow scenarios: no request, under review, approved, partially closed, all unsold, fully closed, complete/incomplete marketing, and empty auction.
- [ ] Use real save/approval/closing actions, valid request data, private demo attachments and validated marketing campaigns. Queue generation snapshots for the details screen; avoid synchronous Chromium rendering during fresh seed.
- [ ] Verify opt-in behavior independently of APP_ENV and all lifecycle/completeness states on isolated CI schemas, repeat-run stability, linked document generation and closed locks.
- [ ] Review diff, run scoped tests/lint and document how to toggle the flag on a server and refresh cached configuration.
