# Auction report visual reference manifest

## Acceptance sources

Reference directory is intentionally external to the repository. Local comparison tooling resolves it from `AUCTION_REPORT_REFERENCE_DIR`; it must never hard-code a user-profile path in production code.

| Document | Source file | SHA-256 | Size | Pages | Rendered MediaBox |
| --- | --- | --- | ---: | ---: | --- |
| Outputs / Haram | `haram-print.pdf` | `E3CE21E270035E308E9F47D919F7B003F415A185B0EB5128AF21025F50ACCE7F` | 7,609,083 bytes | 7 | 595.92 × 841.92 pt |
| Final report / Asir | `asir-print.pdf` | `88624E3BB36950FB9ED6E759471FAE9E58096C39C5727B7E6E93B59BF73385D3` | 10,526,264 bytes | 21 | 594.96 × 841.92 pt |

Both references report `Skia/PDF m151` and PDF 1.4. They are printed package outputs, so the expected small Chromium rounding is documented separately from visual fidelity.

## Reference package integrity

| File | SHA-256 |
| --- | --- |
| `haram.html` | `CBE05B0DAE76C5CCCDF162BF73FAB656B9D79A0773EB9AC4BEC5174E557E1FD5` |
| `asir.html` | `D2C024B60CE31688D14710BC6E53B6DFB634B907A9FE34928D853928FC04D160` |
| `haram-data.json` | `B5B35EBA47E77C67E7267940E03BB2A1A6638AFA06A3239A60EFA2CD10568FE6` |
| `asir-data.json` | `4DD75B53FA68B97221FEF936E30FBBA059B2A92B435F387E6A2EEE3145E2039A` |
| `IBMPlexSansArabic-OFL.txt` | `7E6B2818EDBD8F6A01AE80641CC8F16A51080D08FB4E532BE3A0B6F74ADB07DA` |
| `NotoSansArabic-OFL.txt` | `07FC70BFEB985CC1A87A8587D0A0C80BAB11C86C9DC3FD95B6F0CB332F983E96` |

Integrity check: **PASS** on 26 October 2026.

## Protected acceptance records

The acceptance tenant is `test` / database `etmam_levan_test`.

| Auction ID | Auction name | Properties | Attachments | Existing report generations |
| ---: | --- | ---: | ---: | ---: |
| 5 | جوار الحرم | 11 | 11 | 1 |
| 6 | واجهة عسير | 13 | 51 | 3 |

These records are read-only acceptance inputs. No `migrate:fresh`, `db:wipe`, destructive seeder, data replacement, attachment replacement, or destructive database command is allowed during report-fidelity work.

On 27 October the user confirmed that another operation had accidentally removed these records and explicitly authorized recreating them. The selected rows were recovered from `etmam_levan_test-20261026-125042.sql.gz` without executing the dump or replacing the current database. New IDs are **20 (Haram)** and **21 (Asir)**, with the same 24 properties and 62 attachment files; unrelated current records were retained. Restoration mappings and classification backups are in private backup storage.

The user separately authorized attachment classification/order changes. Classification uses exact file hashes during this development operation, never file-name inference in the production renderer. Haram's eleven old attachments are raster masks/ornaments, not the reference's evidence photographs; those images therefore use the platform-logo fallback. Asir contains fourteen installation photographs, requiring one continuation beyond the thirteen reference frames.

## Runtime boundary

The production flow remains `AuctionReportDataBuilder → AuctionReportRenderer → BrowserDocumentRenderer → tenant_private`.

The HTML and JSON package files are analysis-only artifacts. They must not be included, parsed, or executed by the production report renderer.

## Compiled geometry and fonts

`resources/auction-reports/{haram,asir}/pages.php` records the measured field coordinates, original font/size/vertical scale/color, semantic groups, table columns and row envelopes. The corresponding SVG files retain original static glyph outlines, ornaments, clipping paths and image transforms. `bindings.php` maps semantic values and local image slots; `manifest.php` records SHA-256 for every shipped design asset. Runtime consumes these local files only.

Asir's `image-frames.php` records 69 image definitions' visible rectangles in their original clipping-container coordinate spaces. Missing media and PDF pages are fitted within those rectangles so the source photograph's crop cannot cut off the replacement logo/document. Ordinary photographs retain their original crop behavior.

The complete Noto Sans Arabic and IBM Plex Sans Arabic font files and their OFL texts come from the provided package. Complete GE Contrast Bold and Nizar BBC Kurdish Bold were supplied by the user. Numeric-only STV/Arimo subsets retain all reference digits. Their advance widths are measured once in Chromium and packaged in `fonts/numeric-metrics.php`; no browser is called for text measurement during PHP pagination. Full Nizar is measured directly with FreeType. Arabic presentation forms are used only for measuring connected glyphs; actual PDF text remains Unicode.

**Explicit scope decision, 27 October:** the user deferred matching the remaining secondary fonts and prioritized finalizing layout and manual/automatic extraction. Dynamic text whose original family is not packaged in full currently uses Noto; numeric STV/Arimo runs use their source subsets. This includes secondary TheSans, LamaSans, RuaqArabic, nonnumeric STV and the remaining Latin families. Original static glyph outlines remain unchanged. The static-region comparison must not be described as proof that every dynamic font is identical.

## Visual comparison contract

Golden fixtures contain 7/21 pages with reference-length data. Both PDFs are rasterized using the same `pdftoppm` executable at 96 DPI. Masks are exported from source field envelopes, source table cells and clipped evidence frames. Image masks include one raster pixel for antialiasing. Dynamic data and replaceable pictures are excluded; the Asir watermark is verified using the original landscape, while the replaceable cover crop is masked. Gray diff pixels identify every excluded region; red identifies a mismatch outside it.

Acceptance requires each page's static mismatch ratio to be at most 0.5% with an RGB channel tolerance of 12, mask coverage below 90%, exact page count and MediaBox within 0.02 pt, embedded fonts and extractable text. This measures static artwork fidelity; it is not a percentage claim about changed business values or photographs. The complete page contact sheets, masks, overlays, diffs and JSON results are generated under ignored `output/pdf/reference-comparison`.
