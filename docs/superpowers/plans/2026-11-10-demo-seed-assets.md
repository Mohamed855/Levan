# Demo seeding assets

- [x] Bundle the supplied `images.jpg` and `sample.pdf` under seeder fixtures so deployment does not depend on a local Windows path.
- [x] Use real stored copies for demo settings, user/entity images, assignment/property attachments, auction images/brochures, request attachments, and campaign evidence. Keep per-record paths where deleting one attachment must not affect another.
- [x] Preserve the test-seeder opt-in, existing scenario coverage, approved snapshots, and synchronous generation of actual auction reports/forms.
- [x] Verify stored bytes, file metadata, privacy, idempotence, and ready generated documents using isolated CI databases; run PHP syntax/style checks and a real browser generation smoke test.

Verification: fixture SHA-256 matches both supplied originals. Source-to-storage mismatch tests failed before implementation, then passed. Scoped seeder/access/opt-in tests passed (21 executed, 2640 assertions; optional Chromium test run separately). Real Chromium successfully generated four documents with the new cover images and campaign evidence; sample report pages were visually inspected. PHP syntax and scoped Pint checks passed. Read-only independent review found the shared brochure path; a regression reproduced cross-auction sharing, and brochures now have independent per-auction paths. No business database was freshly seeded or reset.
