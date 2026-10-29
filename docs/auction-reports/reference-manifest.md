# Auction report reference and verification contract

Updated 29 October 2026. This contract supersedes the earlier masked/static-score acceptance.

## Immutable originals

| Design | Original in Downloads/outputs/samples | SHA-256 | MediaBox (pt) |
|---|---|---|---|
| outputs | مخرجات مزاد جوار الحرم .pdf | 7c2d047ff0aaa4d6dcae7117996121d595291c5545deb8e299eab9c6f7bce797 | 595.5 × 842.2499787 |
| report | تقرير مزاد واجهة عسير.pdf | 22d7d2e448f2c6ebac35933b40dac0ad97fa5e79654d32067eb1c7e2fc2f8b6b | 595.276 × 841.89 |

Paths and hashes are recorded in `tests/Fixtures/AuctionReports/acceptance-manifest.json`. Original files are read-only inputs. The reconstructed `haram-print.pdf` / `asir-print.pdf` are historical analysis artifacts, not final acceptance references.

The extracted HTML/JSON packages are analysis-only. Production must not include, parse or execute them. Compiled SVG geometry, bindings, fonts and local asset hashes live under `resources/auction-reports`; `refresh-asset-manifest.php` verifies their integrity.

## Runtime and data safety

Production remains `DataBuilder → Paginator → Data binding → TextLayout → SVG → BrowserDocumentRenderer → tenant_private`.

Mutating acceptance tests require all four explicit settings:

- `DB_DATABASE=levan_report_ci_central`
- empty `DB_URL`
- `AUCTION_REPORT_TEST_TENANT_DATABASE=levan_report_ci_tenant`
- `AUCTION_REPORT_TEST_TENANT_ID=auction_report_ci`

The guard executes before application boot and rechecks effective database/tenant identity. No fresh/seed or migration is authorized against the user's shared databases. The metadata migration has been applied to CI only; deploying it to another tenant requires identifying that tenant explicitly.

Historical records 5/6 were accidentally removed and restored, with permission, as 20/21. These IDs are not a permanent runtime contract. The current acceptance source is the immutable private restoration backup; CI uses its own receipt-mapped IDs, not the original tenant.

## Fonts and geometry

Measurement and rendering resolve the same complete font face. Provided GE, Nizar, STV, TheSans Plain/Bold, LamaSans Bold, Arimo Regular/Bold, Montserrat, Myriad and IBM faces are installed alongside local Arial/Calibri/Symbol. The rejected variable-Noto/static-instance experiment is not used: legacy complete static Noto Regular/Bold passes Arabic search and Unicode checks with normal shaping.

Only **Ruaq Arabic Medium** and **Aptos Narrow Bold** remain explicitly deferred. Aref Ruqaa and Aptos Regular are not equivalent replacements. Static glyph outlines from the original artwork remain unchanged.

Titles fit complete text within two lines, retaining the original size when it fits; an unreadable title fails explicitly. Only designated narratives continue. Numeric scalars never create pages. Table continuations pack measured rows, and single-section result continuations do not repeat an empty opposite table.

Chromium MediaBox rounding must stay within 0.5pt, reported as a measured delta rather than zero. Image/background cover uses rectangular frame geometry and focal coordinates; screenshots/documents use contain. A new photograph is not cut using the old photograph's skyline alpha mask.

## Acceptance sources and source gaps

File-only rendering calls the real DataBuilder with preloaded relations and throws on every DB query. All 62 saved attachments exist, but existence is not proof of usable evidence:

- Haram: 11 design/ornament assets, not restored board photographs or broker contracts. Its saved report background is decorative, not a replacement site landscape.
- Asir: 9 design assets, 2 platform, 2 video, 7 X, 1 WhatsApp, 3 social, 14 installation and 13 removal photographs.
- Missing original brochures, broker contracts/names, contacts, introductions/results narratives and board dimensions/counts remain explicit source gaps. Saved property categories can also differ from the reference's free-form descriptions; do not change a general enum to fabricate a match.
- Asir's complete landscape is a verified exact match to package asset 57f9524018b458c61391, SHA-256 `f3d9171a96a6b1635ceb6976da9a53824184bca7ddb45aa7dbed602a9d0c0fb4`. This override is limited to the file fixture and identity-guarded CI records.

## Visual and functional verification

All four combinations are required: both designs for each auction. Page counts derive from content, not a forced 7/21. The restored file fixtures currently produce Haram 7/20 and Asir 18/22.

`render-reference-comparison.ps1` renders all four, measures browser geometry, renders link proofs, checks PDF contracts, and creates **unmasked** 150-DPI comparisons against the immutable originals. Raster caches are keyed by PDF hash and DPI and accept numeric page filenames only. `render-detail-proof.py` produces selected 300-DPI numeric/table/cover/appendix pages.

No similarity percentage grants acceptance. Full-page differences include changed data, source gaps and deferred fonts; they are not hidden. Strict whole-document ≤1pt placement/≤3RGB flat-color acceptance is **not claimed** until those regions are independently measured and unresolved source/font differences are closed.

`verify-final-artifacts.py` independently checks the eight downloaded manual/auto PDFs and two edge-case PDFs: revision/snapshot pairing, hashes, private-ready status, property values, sales amounts, geometry, Unicode and per-campaign link annotations. Because pypdf reverses some Arabic-Indic numeric runs during bidi extraction, numeric values are checked separately by positioned glyphs through pdfplumber; reversed numbers are not silently accepted.

The current evidence and remaining acceptance limits are documented in `acceptance-results.md`. Full-reference acceptance remains separate from functional export acceptance.
