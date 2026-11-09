# Auction regulatory forms implementation plan

**Goal:** Prepare independently renderable, data-driven establishment and closing forms matching the supplied REGA references, without connecting either management module.

**Architecture:** Database-free PHP renderer accepting a documented array contract; native Blade text, tables and controls with print CSS; reusable original branding assets only. Existing Chromium renderer produces PDFs. Original PDFs are comparison references and never runtime page backgrounds.

**Scope:** Six establishment pages (page four landscape), one repeatable landscape closing page. Preserve source wording, table capacities, signatures and mixed page sizes. Do not modify routes, management screens, models, migrations, or existing user edits. Work in this checkout using new scoped files; no branch mutations or dependency installation.

## Tasks

- [ ] Inspect reference typography, geometry, branding and field semantics; prepare reusable branding/font assets and record provenance.
- [ ] Add failing renderer tests for native content, blank pages, mixed orientation, input escaping, conditional results and table continuation.
- [ ] Implement the independent renderer, print views and stable input contract.
- [ ] Export blank, populated and continuation proofs through Chromium; inspect all pages and measure overflow.
- [ ] Compare every blank page against the originals; record actual differences and unresolved fidelity limits honestly.
- [ ] Review scope/security/simplicity, run relevant tests and deliver files plus integration instructions.

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
