# Haram outputs and establishment signing assets

Scope: the three supplied auction-2 PDFs; preserve Asir appearance and board installation/removal styling.

- [x] Bind Haram cover company, issuing entity, category and cover footers to actual data.
- [x] Center all index and results table headings and values.
- [x] Remove marketing campaign repetition and flow populated platform sections across pages, including TikTok evidence.
- [x] Replace fixed broker inputs with repeatable name/file rows, preserving existing evidence and failed replacement uploads.
- [x] Add optional PNG establishment signature/stamp settings and render them in both regulatory forms.
- [x] Add regression tests, review security/tenant boundaries, generate and visually inspect PDF proofs.
- [x] Run focused PHP/browser tests, style checks and asset build/manifest checks as applicable.

Ownership: root owns report rendering and final integration; dynamic_brokers owns campaign input/save; settings_form_signatures owns settings and regulatory signing.

Verification: use synthetic snapshots and isolated test databases. Never run unrelated pending migrations or modify supplied PDFs.

Evidence: 71 focused PHP tests (412 assertions), 18 Asir/assets regression PHP tests (795 assertions), 14 Haram/broker browser tests, 8 regulatory browser tests, 28 Asir browser tests. Selected Pint, production Vite build and strict PSR autoload check passed. 179 local report assets hashed. Real tenant snapshot build smoke succeeded; no auction data changed. Legacy empty file captions retain their saved campaign broker name; a regression was observed failing and then passed after the compatibility fix.

Applied only the additive signing-assets migration to tenant `test` after reviewing its pretend SQL. Generated proof PDFs under `output/pdf/auction-2-review/`; regulatory examples use conspicuously labelled TEST signing images, never uploaded to application settings.

Separate pre-existing limitation discovered during stress testing: some valid 255-character auction titles exceed Haram's existing two-line/36pt cover-title limit and raise InvalidArgumentException. Left unchanged because redesigning the auction-title area was outside this request; reported to the user. Long company/issuer names and the supplied example auction-name pattern are covered by the new identity test.
