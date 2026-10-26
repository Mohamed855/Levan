# Pixel-Perfect Auction Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generate the auction outputs and final report as server-rendered PDFs that reproduce the supplied “جوار الحرم” and “واجهة عسير” references page-for-page, while replacing only the business data and media with the selected auction’s live tenant data.

**Architecture:** Keep the current `AuctionReportDataBuilder → AuctionReportRenderer → BrowserDocumentRenderer → tenant_private` flow. Replace the generic report presentation with two independent, deterministic Blade print systems: the outputs document follows the Haram reference and the final report follows the Asir reference. The supplied HTML/PDF packages are forensic references only; production rendering must not execute or depend on their editor JavaScript, `saved-data`, or embedded sample values.

**Tech Stack:** Laravel, Blade, PHP, Browsershot/Chromium, CSS Paged Media, local WOFF2/TTF fonts, PHPUnit, Poppler/ImageMagick-compatible local PDF rasterization for visual comparison.

**Spec:** User-approved requirements in the auction-template conversation, with immutable reference sources under `C:\Users\moham\Downloads\auction-templates`.

## Global Constraints

- Do not run `migrate:fresh`, `db:wipe`, destructive seeders, or any command that replaces the current “جوار الحرم” and “عسير” tenant data.
- Treat `haram-print.pdf` and `asir-print.pdf` as the visual source of truth; treat their HTML/JSON files as measurement aids only.
- Mapping: auction outputs use the “جوار الحرم” visual system; the final auction report uses the “واجهة عسير” visual system.
- All auction names, dates, values, property rows, statistics, and replaceable images come from the report snapshot; no reference business value may leak into a live PDF.
- Production rendering remains server-side Blade HTML to PDF and must work without network access.
- Production code must not execute, include, or parse `reports/engines/haram.html`, `reports/engines/asir.html`, `haram-data.json`, `asir-data.json`, or any `saved-data` payload.
- Fonts and approved visual assets must be local, embedded into the rendered HTML/PDF, and covered by an explicit license/provenance manifest.
- Generated PDFs remain on the tenant-private disk and retain the current queue, revision, stale-generation, and cleanup behavior.
- Static design must not be simplified to Bootstrap cards or generic tables; dimensions are specified in points from the references.
- Data overflow must create continuation pages in the same visual system; it must never shrink the whole page, clip content, or silently drop rows/images.
- All implementation tests use an isolated test database. The two live dummy auctions are used only for the final controlled acceptance render.

## Measurable Acceptance Contract

1. With the reference fixture data and images, outputs render as 7 pages and report renders as 21 pages in the original page order.
2. Page MediaBox dimensions match the source: Haram `595.5 × 842.25 pt`; Asir `595.276 × 841.890 pt`, allowing only Chromium’s documented maximum rounding of `0.42 pt` per dimension.
3. Every reference page has a three-panel artifact: reference PNG, generated PNG, and 50%-opacity overlay/diff, rasterized by the same renderer and DPI.
4. Outside explicitly declared dynamic masks, at least `99.5%` of pixels have an RGB channel delta no greater than `8/255`; no single structural region may be displaced by more than `1 pt`.
5. Inside dynamic text/image masks, the bounding box, alignment, font family/weight/size, line height, color, crop mode, border, and padding match the visual contract even though content differs.
6. Arabic text stays connected and searchable; the PDF reports all required fonts as embedded and performs no HTTP font/image request.
7. Long values, missing optional images, zero rows, and overflow rows/images produce intentional template variants without overlap, clipping, or blank accidental pages.
8. A final manual review covers all 28 golden pages and both live-auction outputs; only data and replaceable image pixels may differ from the reference.

## File Map

### Reference analysis and documentation

- Create `docs/auction-reports/reference-manifest.md`: hashes, source paths, page sizes/counts, source provenance, and asset/font licensing decisions.
- Create `docs/auction-reports/haram-visual-contract.md`: per-page layer inventory, coordinates, colors, typography, dynamic regions, and overflow rules.
- Create `docs/auction-reports/asir-visual-contract.md`: the same contract for all 21 source pages.
- Create `docs/auction-reports/data-binding-matrix.md`: each dynamic reference field/image/table mapped to an exact snapshot key or an explicit fallback.

### Runtime report code

- Modify `app/Services/Tenants/Auctions/Reports/AuctionReportDataBuilder.php`: add only fields proven necessary by the binding matrix, preserving tenant storage isolation.
- Modify `app/Services/Tenants/Auctions/Reports/AuctionReportRenderer.php`: select the exact document definition and expose deterministic render metadata.
- Create `app/Services/Tenants/Auctions/Reports/AuctionReportPaginator.php`: convert variable arrays into named page payloads using reference capacities and continuation rules.
- Create `app/Services/Tenants/Auctions/Reports/AuctionReportFormat.php`: centralized Arabic date, number, currency, and missing-value formatting required by both templates.
- Create `config/auction-report-templates.php`: document IDs, page dimensions, page definitions, row/image capacities, and local asset/font paths.

### Blade print system

- Replace `resources/views/tenant/auctions/reports/outputs.blade.php`: outputs document orchestration only.
- Replace `resources/views/tenant/auctions/reports/report.blade.php`: final report orchestration only.
- Replace `resources/views/tenant/auctions/reports/partials/styles.blade.php`: exact shared print reset, font faces, page rules, and primitives; remove generic Almarai report styling where it conflicts with the references.
- Create `resources/views/tenant/auctions/reports/layouts/document.blade.php`: zero-margin print shell and deterministic asset embedding.
- Create `resources/views/tenant/auctions/reports/partials/haram/*.blade.php`: one focused partial per measured Haram page family.
- Create `resources/views/tenant/auctions/reports/partials/asir/*.blade.php`: one focused partial per measured Asir page family.
- Create `resources/views/tenant/auctions/reports/partials/shared/*.blade.php`: only genuinely identical low-level elements such as embedded logo/image helpers and page footer primitives.
- Create `resources/auction-reports/fonts/*`: approved complete Arabic font files used for dynamic values.
- Create `resources/auction-reports/haram/*` and `resources/auction-reports/asir/*`: approved static SVG/raster ornaments, backgrounds, and logos extracted from the supplied sources.

### Tests and visual tooling

- Modify `tests/Unit/Auctions/AuctionReportDataTest.php`: binding completeness and no-reference-data leakage.
- Modify `tests/Unit/Auctions/AuctionReportRendererTest.php`: page families, page counts for golden fixtures, CSS dimensions, embedded resources, and forbidden engine dependencies.
- Create `tests/Unit/Auctions/AuctionReportPaginatorTest.php`: exact boundary and continuation behavior.
- Modify `tests/Feature/Tenants/Auctions/AuctionReportGenerationTest.php`: real two-document render contract and private storage assertions.
- Preserve `tests/Feature/Tenants/Auctions/AuctionReportIsolationTest.php` and `AuctionReportWorkflowTest.php`; extend only where new fields or errors cross their contract.
- Create `tests/Fixtures/AuctionReports/haram.php` and `tests/Fixtures/AuctionReports/asir.php`: sanitized PHP snapshot fixtures matching the reference documents without runtime JSON dependencies.
- Create `scripts/auction-reports/render-reference-comparison.ps1`: render PDFs, rasterize pages at a fixed DPI, build overlays/diffs, and write a machine-readable summary.
- Create `scripts/auction-reports/assert-pdf-contract.ps1`: assert page count, MediaBox, font embedding, and absence of network/resource failures.
- Keep large original reference PDFs outside the repository unless explicit approval is given; identify them by SHA-256 from the manifest.

## Review Focus

- Arabic names longer than the original reference: preserve size first, then use a documented page-specific multiline/overflow rule; never globally scale the design.
- Missing cover/report/property/evidence images: render the approved platform-logo fallback with the exact target frame and crop geometry.
- Properties or campaign rows exactly at, one below, and one above a page capacity: no duplicate, dropped, or split row.
- Transparent PNG/SVG and EXIF-rotated JPEG inputs: correct orientation, background, and `cover` crop in the final PDF.
- Same report regenerated after data changes: new snapshot is rendered, stale output is not published, and no reference/sample value remains.

---

### Task 1: Freeze the evidence and protect the live dummy data

**Files:**
- Create: `docs/auction-reports/reference-manifest.md`
- No database writes.

**Interfaces:**
- Consumes: supplied reference directory and current tenant auction records.
- Produces: immutable reference hashes and the exact IDs/names of the two acceptance auctions.

- [ ] Record SHA-256, byte size, PDF page count, and MediaBox for both PDFs plus hashes for HTML/JSON/license files.
- [ ] Record the current tenant identifier, auction IDs, relevant attachment counts, and storage paths using read-only queries.
- [ ] Confirm the source directory used by scripts through `AUCTION_REPORT_REFERENCE_DIR`; do not hard-code a developer profile path into application code.
- [ ] Add an execution guard to the plan/runbook prohibiting `migrate:fresh`, `db:wipe`, and seed commands during acceptance work.
- [ ] Verify hashes and record `PASS` in the manifest before any visual implementation.
- [ ] Commit documentation only.

### Task 2: Extract the complete visual contracts

**Files:**
- Create: `docs/auction-reports/haram-visual-contract.md`
- Create: `docs/auction-reports/asir-visual-contract.md`
- Create: `docs/auction-reports/data-binding-matrix.md`

**Interfaces:**
- Consumes: reference PDFs as visual truth; HTML packages as coordinate/asset/font evidence.
- Produces: a page-by-page specification used by all later tasks.

- [ ] Rasterize all 7 Haram and 21 Asir pages at one fixed DPI and save analysis output outside version control.
- [ ] For every page, inventory background, static vector/raster layers, logos, headings, dynamic text, dynamic images, tables, footer, and page number in z-order.
- [ ] Record all measurements in points: `x`, `y`, width, height, rotation, border width/radius, padding, gap, and crop anchor.
- [ ] Record exact colors as hex/rgba, including gradients, opacity, shadows, alternating rows, and strokes.
- [ ] Record font family, required full-font substitute where the embedded subset cannot render arbitrary Arabic, weight, size, line height, letter spacing, alignment, and baseline behavior.
- [ ] Mark every region as `static`, `dynamic_text`, `dynamic_image`, `dynamic_table`, or `computed` and give each dynamic region one snapshot key.
- [ ] Define the original page order and identify repeatable page families for properties, marketing, boards, and evidence.
- [ ] Define continuation behavior and the exact capacity of every table/gallery page family.
- [ ] Review both contracts against every source page before moving to implementation.
- [ ] Commit the three reviewed contracts.

### Task 3: Approve and package fonts, logos, and static artwork

**Files:**
- Create: `resources/auction-reports/fonts/*`
- Create: `resources/auction-reports/haram/*`
- Create: `resources/auction-reports/asir/*`
- Modify: `docs/auction-reports/reference-manifest.md`

**Interfaces:**
- Consumes: visual contracts and supplied OFL notices/assets.
- Produces: offline, versioned, licensed assets with stable identifiers.

- [ ] Extract each static background/ornament/logo at its original resolution or as SVG without re-compressing it.
- [ ] Classify logos as approved brand assets or assets requiring user confirmation; do not ship an unapproved government/third-party mark.
- [ ] Package complete Noto Sans Arabic and IBM Plex Sans Arabic faces needed for arbitrary dynamic Arabic and record their OFL provenance.
- [ ] If a reference commercial subset font has no complete licensed file, choose the visually closest approved complete font per text role and record the unavoidable substitution explicitly.
- [ ] Write hash-based asset tests so accidental recompression/replacement fails.
- [ ] Verify all resources load from local paths and no CSS contains an HTTP URL.
- [ ] Commit assets and provenance together.

### Task 4: Lock the semantic snapshot and formatting contract

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionReportDataBuilder.php`
- Create: `app/Services/Tenants/Auctions/Reports/AuctionReportFormat.php`
- Modify: `tests/Unit/Auctions/AuctionReportDataTest.php`
- Create: `tests/Unit/Auctions/AuctionReportFormatTest.php`

**Interfaces:**
- Consumes: an `Auction` in the active tenant context.
- Produces: `AuctionReportDataBuilder::build(Auction): array` and deterministic formatter methods used by page partials.

- [ ] Write failing tests for every key in `data-binding-matrix.md`, including image order, board data, marketing statistics, result totals, dates, and optional values.
- [ ] Run the focused tests and confirm they fail only for missing bindings/formatting.
- [ ] Add the minimum snapshot fields required by the matrix; do not couple the builder to page coordinates or reference page numbers.
- [ ] Implement explicit Arabic date/number/currency/status formatting methods; remove view-local formatting duplication as each page migrates.
- [ ] Add tests proving the snapshot contains no source reference values unless they are the live auction’s values.
- [ ] Run the data/format tests and commit the passing semantic contract.

### Task 5: Implement and prove deterministic pagination

**Files:**
- Create: `app/Services/Tenants/Auctions/Reports/AuctionReportPaginator.php`
- Create: `config/auction-report-templates.php`
- Create: `tests/Unit/Auctions/AuctionReportPaginatorTest.php`
- Create: `tests/Fixtures/AuctionReports/haram.php`
- Create: `tests/Fixtures/AuctionReports/asir.php`

**Interfaces:**
- Consumes: snapshot array plus document type `outputs|report`.
- Produces: `AuctionReportPaginator::paginate(array $snapshot, string $type): array` where each element has a stable `page_type`, `page_number`, and page-specific payload.

- [ ] Write boundary tests for `0`, `capacity - 1`, `capacity`, and `capacity + 1` rows/images for every repeatable page family.
- [ ] Write golden fixture tests expecting exactly 7 Haram pages and 21 Asir pages in the reference page order.
- [ ] Run the paginator tests and confirm failure before implementation.
- [ ] Encode page definitions and capacities from the visual contracts, not estimates from the current generic views.
- [ ] Implement pagination with stable sorting and continuation page identifiers.
- [ ] Assert no input record is duplicated, dropped, or split across incompatible page types.
- [ ] Run tests and commit the paginator and fixtures.

### Task 6: Build the exact Haram outputs document

**Files:**
- Replace: `resources/views/tenant/auctions/reports/outputs.blade.php`
- Create: `resources/views/tenant/auctions/reports/layouts/document.blade.php`
- Create: `resources/views/tenant/auctions/reports/partials/haram/*.blade.php`
- Create: `resources/views/tenant/auctions/reports/partials/shared/*.blade.php`
- Modify: `resources/views/tenant/auctions/reports/partials/styles.blade.php`
- Modify: `tests/Unit/Auctions/AuctionReportRendererTest.php`

**Interfaces:**
- Consumes: Haram page payloads from `AuctionReportPaginator`.
- Produces: complete offline HTML for the outputs PDF.

- [ ] Write failing renderer assertions for all Haram page types, exact page dimensions, required font faces/assets, and absence of engine/sample dependencies.
- [ ] Implement the zero-margin document shell and exact `@page` rule for Haram.
- [ ] Implement each Haram page family in reference order using point-based absolute/grid geometry from the visual contract.
- [ ] Bind every dynamic region to the semantic snapshot; use the platform-logo fallback only for optional missing images.
- [ ] Match cover, headers, tables, row striping, image crop/position, ornaments, and footer/page numbering without generic report components.
- [ ] Render the golden fixture and complete the first 7-page overlay review.
- [ ] Correct geometry/styles until the measurable acceptance threshold passes on every Haram page.
- [ ] Run unit and render-contract tests, then commit the complete outputs document.

### Task 7: Build the exact Asir final-report document

**Files:**
- Replace: `resources/views/tenant/auctions/reports/report.blade.php`
- Create: `resources/views/tenant/auctions/reports/partials/asir/*.blade.php`
- Modify: `resources/views/tenant/auctions/reports/partials/styles.blade.php`
- Modify: `tests/Unit/Auctions/AuctionReportRendererTest.php`

**Interfaces:**
- Consumes: Asir page payloads from `AuctionReportPaginator`.
- Produces: complete offline HTML for the final report PDF.

- [ ] Write failing renderer assertions for all Asir page types, exact MediaBox, required assets/fonts, and 21-page golden order.
- [ ] Implement the exact Asir `@page` rule independently from Haram.
- [ ] Implement all Asir page families in source order and bind their text, tables, statistics, property imagery, marketing evidence, and board evidence.
- [ ] Match the reference SVG geometry, whitespace, typography hierarchy, table rules, image frames, and page footer details.
- [ ] Render the golden fixture and complete the 21-page overlay review.
- [ ] Correct geometry/styles until the measurable acceptance threshold passes on every Asir page.
- [ ] Run unit and render-contract tests, then commit the complete report document.

### Task 8: Make Chromium output deterministic

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionReportRenderer.php`
- Modify: `config/auction-renderer.php`
- Modify only if required: the current `BrowserDocumentRenderer` implementation/binding.
- Modify: `tests/Unit/Auctions/AuctionReportRendererTest.php`

**Interfaces:**
- Consumes: full page payload and local assets.
- Produces: deterministic HTML/PDF under the existing renderer contract.

- [ ] Add tests for `printBackground`, `preferCSSPageSize`, zero margins, font-ready/image-ready completion, and no external network access.
- [ ] Pass the paginator result and document metadata to Blade without exposing reference editor files.
- [ ] Pin rendering options and viewport/device scale required by the comparison environment.
- [ ] Wait for `document.fonts.ready` and all images before PDF capture; surface a failed resource as a generation error.
- [ ] Verify two consecutive renders of the same fixture have identical page geometry and materially identical rasters.
- [ ] Run renderer tests and commit deterministic rendering.

### Task 9: Automate visual and PDF contract verification

**Files:**
- Create: `scripts/auction-reports/render-reference-comparison.ps1`
- Create: `scripts/auction-reports/assert-pdf-contract.ps1`
- Modify: `tests/Feature/Tenants/Auctions/AuctionReportGenerationTest.php`
- Modify as required: `docs/auction-report-worker.md`

**Interfaces:**
- Consumes: the reference directory, generated PDFs, and dynamic-mask definitions from the visual contracts.
- Produces: PNGs, overlays, diff heatmaps, `comparison-summary.json`, and pass/fail exit code.

- [ ] Make the comparison script validate reference SHA-256 before comparing.
- [ ] Rasterize source and generated PDFs with one executable, DPI, color profile, antialiasing mode, and page dimensions.
- [ ] Generate reference/generated/overlay/diff artifacts for all pages and apply only documented dynamic masks.
- [ ] Calculate per-page mismatch ratio and structural displacement; fail when any acceptance threshold is exceeded.
- [ ] Assert PDF page count, MediaBox, embedded fonts, text extractability, and zero remote requests.
- [ ] Extend the feature test to prove both documents are stored privately and retain the existing stale-generation cleanup semantics.
- [ ] Document the one-command comparison workflow and commit tooling/tests.

### Task 10: Exercise dynamic-content failure modes

**Files:**
- Modify: `tests/Unit/Auctions/AuctionReportPaginatorTest.php`
- Modify: `tests/Unit/Auctions/AuctionReportRendererTest.php`
- Modify: `tests/Feature/Tenants/Auctions/AuctionReportGenerationTest.php`

**Interfaces:**
- Consumes: production paginator, views, and renderer.
- Produces: regression proof for data unlike the original reference.

- [ ] Test long Arabic auction/property/entity names at each constrained text region.
- [ ] Test empty optional description, city, manager, platform, marketing, property result, and attachment sections.
- [ ] Test missing cover/report/property/evidence images and assert the exact platform-logo fallback frame.
- [ ] Test one-more-than-capacity tables and galleries and assert continuation headers/footers and record conservation.
- [ ] Test mixed image aspect ratios, transparency, EXIF rotation, and large source dimensions.
- [ ] Test that no reference auction name, amount, date, property number, or embedded sample image appears in a live-data render.
- [ ] Run the focused suite and commit passing edge-case coverage.

### Task 11: Render the preserved live auctions and obtain visual sign-off

**Files:**
- No product-code changes unless a failed acceptance check identifies a scoped defect.
- Write generated review artifacts outside version control under `output/auction-report-comparison/<timestamp>/`.

**Interfaces:**
- Consumes: preserved “جوار الحرم” and “عسير” auction records.
- Produces: two final PDFs plus complete visual evidence.

- [ ] Reconfirm tenant/auction IDs and record counts match Task 1 before generation.
- [ ] Generate both document types through the real queued application workflow; do not call Blade manually for final acceptance.
- [ ] Download through the authorized tenant endpoint and confirm cross-tenant access remains denied.
- [ ] Run page-size/font/resource checks on both PDFs.
- [ ] Produce side-by-side and overlay artifacts for every page; inspect all pages, not only covers.
- [ ] Verify each visible difference belongs only to a changed data or replaceable image region.
- [ ] Present the two PDFs and comparison summary for user sign-off.
- [ ] If sign-off exposes a mismatch, add a failing regression assertion first, patch only the owning page family, rerun its visual comparison, then rerun the complete 28-page golden comparison.

### Task 12: Final verification and handoff

**Files:**
- Update: `docs/auction-reports/reference-manifest.md`
- Update: `docs/auction-report-worker.md`

**Interfaces:**
- Consumes: completed implementation and acceptance artifacts.
- Produces: release-ready evidence and repeatable maintenance workflow.

- [ ] Run auction-report unit and feature tests.
- [ ] Run the full relevant Laravel test suite.
- [ ] Run `npm run build` only if any `.css`, `.scss`, or `.js` source was changed; the preferred implementation keeps print CSS in Blade and should not require frontend asset changes.
- [ ] Run both visual comparisons from a clean application process and record tool/browser versions.
- [ ] Perform a security review of tenant boundaries, private files, authorization, attachment paths, and HTML escaping.
- [ ] Perform a simplification review to remove dead generic report CSS and duplicated page logic without changing visual output.
- [ ] Re-run all tests and visual comparisons after cleanup.
- [ ] Record final reference hashes, generated page counts, similarity results, known licensed font substitution if any, and user sign-off.

## Execution Order and Gates

1. **Evidence gate:** Tasks 1–2 must be approved before visual code changes.
2. **Asset/license gate:** Task 3 must resolve logo/font reuse before those assets enter production.
3. **Data gate:** Tasks 4–5 must pass before either Blade document is rebuilt.
4. **Haram gate:** Task 6 must pass all 7 pages before starting Asir.
5. **Asir gate:** Task 7 must pass all 21 pages before runtime hardening.
6. **Automation gate:** Tasks 8–10 must pass before using the preserved live auctions.
7. **Acceptance gate:** Tasks 11–12 require page-by-page user sign-off; “looks close” is not completion.

## Explicit Non-Goals

- Rebuilding or exposing the standalone HTML reference editor.
- Loading the reference JSON/HTML packages at runtime.
- Redesigning auction CRUD, permissions, samples, or the template designer.
- Changing queue/storage/authorization behavior except where required to preserve deterministic report generation.
- Fresh-seeding or replacing the current tenant data.
