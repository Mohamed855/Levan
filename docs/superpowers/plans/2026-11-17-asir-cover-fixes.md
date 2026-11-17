# Asir Cover Reliability Implementation Plan

> **For agentic workers:** Use `executing-plans` to implement this plan task by task after the user authorizes implementation. No implementation is authorized by the planning request alone.

**Goal:** Fix the reproduced Asir cover failures while preserving the existing backgrounds, decorative artwork, fonts, palette and two-cover structure.

**Architecture:** Retain source artwork as decorative layers. Render variable titles, logos and contact groups as actual HTML/CSS. Measure these elements before the existing Asir readiness flag is set. Cover content remains inside its cover and does not participate in internal-section pagination.

**Tech Stack:** Existing Laravel/Blade, scoped CSS, local report fonts, JavaScript measurement and the application's Chromium PDF renderer. No new dependency.

**Spec:** User-requested plan based on `output/pdf/asir-cover-audit/review.html`, `receipt.json` and `measurements.json`. Thirteen cases were exercised; five failed production HTML rendering, and rendered PDFs demonstrated contact/icon overlap, unsuitable logo contrast and an opaque branding backdrop.

## Global Constraints

- Scope is the first and last Asir covers and their necessary rendering/verification integration.
- Preserve uploaded logo colors and aspect ratios; use a compact contrast backing only where needed.
- Preserve complete accepted input values: auction name up to 255 characters, phone fields up to 30 and social contact up to 255.
- Preserve existing field validation, contact destinations, authorization, tenant boundaries and generation workflow.
- Keep the original two-cover structure. Do not truncate values, force a third cover, distort logos or mask source artwork with a full-width branding rectangle.
- Keep all current internal Asir pages and Haram behavior unchanged. Preserve concurrent workspace changes. No application database writes.

## Review Focus

- Maximum Arabic/Latin names and words without spaces fit at readable sizes without losing characters or colliding with the subtitle.
- Phone text and its icon reserve separate space even for international formatting and maximum accepted length.
- Dark/light transparent logos remain distinguishable on both cover backgrounds; wide/tall logos preserve their ratios.
- Missing values omit their whole contact group; incomplete branding does not restore stale template branding or leave an opaque masking band.
- Final PDF output agrees with the measured HTML and preserves the reference design for ordinary inputs.

### Task 1: Make the front-cover title region fit actual data

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AsirNativeContent.php`
- Modify: `app/Services/Tenants/Auctions/Reports/AsirReportData.php`
- Modify: `resources/views/tenant/auctions/reports/layouts/document.blade.php`
- Create: `resources/views/tenant/auctions/reports/partials/asir-cover.blade.php`
- Modify: `resources/views/tenant/auctions/reports/partials/asir-styles.blade.php`
- Modify: `resources/auction-reports/asir/layout.js`
- Create: `tests/Browser/AsirCovers.test.mjs`
- Modify: `tests/Unit/Auctions/AsirNativeContentTest.php`

**Interfaces:** Add `AsirNativeContent::prepareCover(array $content, array $snapshot): array` for cover indices 0/20. Keep its preparation separate from the internal marketing template match. Add a dedicated cover root/measurement hook; preserve the existing `window.asirReportReady`/`window.asirReportError` contract.

- [x] Add failing regressions using the audited 31-character name, 99-character Arabic name and maximum 255-character Arabic/Latin names. Assert full text preservation, no generation exception, centered titles and bounds separated from the subtitle.
- [x] Separate the existing decorative cover artwork from dynamic text/branding using source geometry. Reuse the source artwork; do not redesign the image crop or decorative curves. Final decorative asset names/element removal must be confirmed against the SVG during extraction.
- [x] Render name/subtitle as a shared flowing title region. Start with source typography; allow additional wrapped lines and adjust font size/spacing within the available cover area. Set the readable lower bound through actual PDF comparison, not an arbitrary tiny fallback. Normalize layout whitespace without removing text.
- [x] Branch the document's decorative layer for covers so that blue internal-page panels are not added. Measure cover bounds before reporting ready.
- [x] Verify all name cases through browser measurement and the application's actual PDF renderer. Update the old direct-SVG-cover assertion to check retained decorative artwork plus native dynamic content.

### Task 2: Fit closing contacts and preserve the closing artwork

**Files:**
- Create: `resources/views/tenant/auctions/reports/partials/asir-closing.blade.php`
- Modify: the preparation, document layout, scoped styles and layout script from Task 1.
- Test: `tests/Browser/AsirCovers.test.mjs`

**Interfaces:** Consume prepared phone/unified-phone/social display values and the existing `AuctionReportLinks` destinations. Each contact group owns its icon, full value and link. Decorative source artwork must exclude the original fixed contact symbols and replaced logo so these are not duplicated.

- [x] Add failing regressions for `+966 55 123 4567`, 18 digits, 30-character accepted phone values, a valid 255-character social URL and missing/null contacts. Assert separate icon/text rectangles and complete values.
- [x] Build contact groups with separate icon and text columns, a real gap and LTR phone text. Wrap long values inside their allotted area while preserving the complete value and existing safe link.
- [x] Omit the entire contact group when its value is missing. Keep the original X icon for the current social field in this repair; platform detection would be a separate product decision.
- [x] Use the original background/decorations directly rather than the current full-width branding mask. Keep the thank-you wording and source visual proportions; let contact spacing adapt when a value wraps.
- [x] Verify that long or missing contacts neither overlap each other nor add blank/extra cover pages.

### Task 3: Handle logo contrast and verify both covers

**Files:**
- Modify: Asir cover preparation and scoped styles from Tasks 1/2.
- Test: `tests/Browser/AsirCovers.test.mjs`
- Modify if decorative assets change: `resources/auction-reports/manifest.php`, using the existing refresh script.

**Interfaces:** Consume current `branding.company_logo`/`branding.issuing_entity_logo` data images. Preserve the original asset fallback policy for legacy snapshots. A missing current logo must not introduce another organization's old logo.

- [x] Add regressions using dark/white transparent PNGs, opaque images, wide/tall images and missing branding. Verify complete image bounds, aspect ratio and readable contrast in rasterized PDFs.
- [x] Keep each logo within a padded frame using contain. Use a compact neutral backing when transparent artwork lacks contrast; keep the source logo colors unchanged. Confirm the contrast choice against the actual pixels before printing.
- [x] Re-run the original 13-case audit and additional cover regressions. Compare each cover against its original at normal inputs and visually inspect the extreme cases. Preserve the original design; record any necessary spacing/font adaptation for long inputs.
- [x] Run focused PHP tests, Asir cover/internal browser tests, Haram regressions, Pint and scoped syntax/BOM/diff checks. Confirm generated PDF page counts and readiness behavior.
- [x] Deliver before/after review images and actual PDFs, with a point-by-point outcome for every reproduced issue. Do not claim exact visual identity beyond what the comparison establishes.

## Execution Order

Fix front-cover generation failures, then closing contact fit/visibility, then logo contrast and final visual comparison. Deliver this as one bounded Asir-cover change; no unrelated refactoring, database changes or generation-button changes.

## Execution ledger

- User authorized implementation with “go on”. Ruling: continue in the existing shared checkout as specified in the approved plan, preserving concurrent edits; do not create another checkout or commit unrelated work.
- RED: all 10 new cover browser tests failed before implementation. Long-name/contact cases reproduce the production title-fit exception; ordinary covers lack native content and conditional groups.
- Ruling: preserve the original SVG files and expose a narrowly scoped decorative rendering option for Asir indices 0/20. Remove only the known source logo/contact groups from that decorative output. This retains dynamic report-image cropping and source curves without opaque repainting or duplicating a whole template asset.
- GREEN: the first 13 browser checks passed, including actual application-PDF pixel checks for logo backings and the visible photograph. Ordinary single-line titles are pinned to independently measured source geometry within 1.5pt.
- Fresh review found accepted Unicode numeric symbols can be wider than standard digits. Added failing regressions for both 30-character Unicode phones plus a 255-character URL, and for pasted social text containing 200 newlines. Collective fitting now adapts exceptional groups down to a readable 18pt phone size and, if necessary, moves their region upwards while keeping it below the original thank-you text and above the 820pt bottom limit. Display whitespace collapses without altering the underlying value or safe-link policy. All three targeted contact cases passed; independent follow-up review found no further important issue.
- Ruling: keep the source thank-you lettering as fixed decorative SVG glyphs; titles, logos, phone numbers and social values are native HTML. This preserves the original fixed lettering exactly while all variable content is measurable.
- Final PDF visual QA exposed decorative strokes intersecting expanded 255-character titles. Two additional regressions reproduced this before the fix. The original six paths are now grouped without changing their path data; only expanded titles move that layer downward below their text region. Both regressions passed and independent follow-up review found no important issue. Ordinary source geometry and photograph placement stay unchanged.
- Comparison PNGs use Poppler. The Node canvas PDF rasterizer incorrectly flattened the legacy issuing-logo luminosity mask in both original and native PDFs; Poppler verified the logo correctly in the actual application-generated file. No production logo change was made for this inspection-tool artifact.
- Final verification: 131 focused PHP tests passed with 1,462 assertions; all 38 browser tests passed, including Asir cover/content and Haram regressions. Pint, JavaScript syntax, scoped whitespace and UTF-8 BOM checks passed. The asset manifest verifies 179 local assets.
- Fifteen scenarios were generated by the application's injected PDF renderer, producing all 30 cover images. Normal/legacy before-and-after comparisons and long-name/contact/contrast/empty-state examples were inspected from Poppler-rendered PDF pages. Internal continuation pages account for the six-page long-name examples; both covers remain single pages.
- Delivery: `output/pdf/asir-cover-fixes/review.html`, `normal.pdf`, the per-scenario PDFs/PNGs and `receipt.json`. Current source template SVG files are unchanged. No application database writes, unrelated module edits, commit or publication was performed by this task.
