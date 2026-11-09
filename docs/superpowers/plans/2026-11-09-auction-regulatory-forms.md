# Auction regulatory forms implementation plan

**Goal:** Prepare independently renderable, data-driven establishment and closing forms matching the supplied REGA references, without connecting either management module.

**Architecture:** Database-free PHP renderer accepting a documented array contract; native Blade text, tables and controls with print CSS; reusable original branding assets only. Existing Chromium renderer produces PDFs. Original PDFs are comparison references and never runtime page backgrounds.

**Scope:** Six establishment pages (page four landscape), one repeatable landscape closing page. Preserve source wording, table capacities, signatures and mixed page sizes. Do not modify routes, management screens, models, migrations, or existing user edits. Work in this checkout using new scoped files; no branch mutations or dependency installation.

## Tasks

- [x] Inspect reference typography, geometry, branding and field semantics; prepare reusable branding/font assets and record provenance.
- [x] Add failing renderer tests for native content, blank pages, mixed orientation, input escaping, conditional results and table continuation.
- [x] Implement the independent renderer, print views and stable input contract.
- [x] Export blank, populated and continuation proofs through Chromium; inspect all pages and measure overflow.
- [x] Compare every blank page against the originals; record actual differences and unresolved fidelity limits honestly.
- [x] Review scope/security/simplicity, run relevant tests and deliver files plus integration instructions.

## Verification record

- Nine scoped PHP tests pass (75 assertions). Five real Chromium tests pass, including actual valid exports and failed-export rejection.
- Six proof PDFs verified: 6/6/9 establishment pages and 1/1/3 closing pages; mixed page dimensions and native text verified.
- Independent review caught and reverified fixes for the browser predicate and unbounded property-type values. Usage Other checkbox added.
- Full project suite also attempted: 1014 tests, 259 errors and 38 failures, with isolated-database guards and unrelated module failures. This is not a green full-suite claim. See the output test summary.
- Seven unmasked page comparisons produced. Exact visual equality is false; typography/spacing differences remain. Full source REGA/Bukra font files were unavailable. The explicit 100% visual-match requirement remains unfulfilled and is a delivery limitation, not an approved relaxation.

## Acceptance

- No rasterized form/page background, imported whole-page SVG, field stamping or PDF-form filling.
- Static copy and dynamic values remain text; table cells remain actual HTML table cells.
- Empty input produces the full six-page establishment form and one-page closing form.
- Property/agent/result rows continue without data loss; escaping prevents input from becoming HTML.
- Rendered PDFs preserve A4 portrait/landscape order and Arabic content. Generated blank and populated files are reviewable without database access.
- Visual differences are measured, not described as an unverified 100% match.

## Progress and decisions

- References inspected in the previous turn: establishment 6 pages, closing 1 page, both AcroForms. Source text includes damaged Unicode mappings on some table headings; visually transcribe those headings.
- Existing checkout has three unrelated modified files. Leave them untouched.
- Full REGA/Bukra files have not been found. Inspect embedded subsets before selecting a typography strategy; do not silently substitute fonts and claim exact fidelity.
