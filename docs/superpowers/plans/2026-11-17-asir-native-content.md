# Asir native content implementation plan

**Goal:** Convert Asir internal report pages to real HTML/CSS while preserving the existing visual design, data selection, and PDF generation workflow.

**Architecture:** Keep both SVG covers unchanged. Map existing Asir page payloads into focused Blade partials, with CSS backgrounds and decorative panels. Measure actual rendered content in the browser and continue each section under its original heading. Keep Haram and tenant/database behavior unchanged.

**Scope:** Index, introduction, summary, planning, design, promotion, installation/removal boards, brochure, platform, video, X, WhatsApp, social, brokers, sold/unsold properties, evidence appendices. Reuse the existing font catalog, media preparation, and safe link filtering. No dependencies, unrelated UI changes, or database writes.

## Steps

- [x] Capture the current SVG output and page geometry for visual comparison using identical snapshot data.
- [x] Add failing tests for real HTML content, covers retained, safe/escaped data, conditional sections, and zero-valued metrics.
- [x] Implement Asir data preparation, native Blade partials, and scoped CSS matching original frames, typography, colors, and background treatment.
- [x] Implement measured pagination, including long paragraphs/tables and evidence continuation; wire renderer readiness into the PDF browser.
- [x] Render reference, populated, sparse, long, and many-item cases. Compare every template family against its SVG reference; keep a side-by-side review artifact.
- [x] Run focused PHP/browser checks, style/syntax checks, and Haram regression checks. Review the final diff and record limitations honestly.

## Verification focus

- Preserve every property and attachment exactly once across regular continuation pages; keep campaign contexts independent.
- Preserve long narrative text and long cell contents, including RTL/LTR identifiers and explicit line breaks.
- Retain link safety, image fit/focal positions, document page captions, and escaped user data.
- Omit absent evidence/metrics while retaining recorded zeros; preserve existing section presence rules.
- Ensure PDF page count matches measured DOM pages; covers remain on the existing artwork path.

## Decisions and progress

- User explicitly authorized this conversion after the architecture comparison; proceed in the shared checkout, preserving concurrent user edits. Initial working tree clean.
- Visual parity means keeping the existing Asir design, not applying Haram's appearance. Shared approach means native layout and measured continuation.
- Background photographs and logos remain image assets; text, tables, cards, frames, and panels become HTML/CSS.
- Asir table cells retain their original centered alignment; Haram-specific alignment changes are not transferred to Asir.
- Native pages omit unrecorded image/metric placeholders. Recorded-zero values, all actual rows, source image fit/focal metadata, and document page captions remain present.
- Independent review identified missing platform duration and gallery channel/fallback captions. Both were restored with a failing-then-passing regression test.
- Reference image-frame measurements are pinned in browser tests (installation, video, X, WhatsApp, brokers; tolerance 1.5pt). Both populated/reference fixtures retain their original section structure.
- Tests that require `levan_report_ci_central` / `levan_report_ci_tenant` cannot run in this environment: isolated schemas are absent. No application database was changed. Renderer proofs explicitly reject database queries.
- Other module changes appeared concurrently in the shared checkout (plans, checkout, dashboard, seeders). They were left untouched.
- Final review reproduced an internal thumbnail overflow with a valid 255-character name. Names fit the original blue frame down to a readable 8pt; if still too tall, the complete name becomes a normal flowing paragraph in the same section. No characters are truncated and attachments are not duplicated. Maximum-length Latin/Arabic names are covered by a browser regression.
- Appendix galleries retain the original LTR slot order and 190x100pt frames at (95,350), (310,350), (95,485); captions retain channel labels and document pages. Generic campaign appendices retain their original body heading, metrics, and footer link layout. The campaign identity repeats on continuation pages.
- Broker letterheads retain the original decorative pattern and use the current company's logo for the watermark when branding is provided.
- A 255-character document caption exposed another full-page figure overflow. If its caption cannot fit, the image height adapts to the actual remaining space (without changing fit/focal metadata); the complete caption remains visible. Broker captions use navy on the white letterhead. A browser regression checks text preservation, media identity, and physical PDF page count.
- Application-rendered PDF inspection exposed Chromium printing luminance masks as solid rectangles. Four transparent PNGs were extracted from the original template masks, preserving their pixels, colors and placement. Ordinary image elements now print WhatsApp/X symbols and broker decorations correctly. A PDF pixel regression also verifies that a 100% delivery chart has no zero-segment seam.
- Final side-by-side review restored the original opposing curves and colored icon areas of social metric badges and the slanted corners of the unsold table using CSS. These details have a browser regression.

## Final verification

- 127 focused PHP/report tests passed with 1,420 assertions; 20 browser tests passed, including Haram regressions and actual application-PDF pixel checks.
- Pint, JavaScript syntax checks, scoped diff whitespace checks and UTF-8 BOM checks passed. The asset manifest verifies 175 local assets.
- Fresh comparison renders completed with no readiness/layout errors: reference 21 pages, populated 21, sparse 5, many 53, long 133, appendix 21. Every physical PDF count matches the measured HTML page count.
- The application's injected PDF renderer generated the populated 21-page proof. Rasterized PDF pages 2, 11–16, 19 and 20 were inspected, including the printed WhatsApp/X symbols, broker watermark/pattern, social badges and unsold table corners.
- Review artifacts: `output/pdf/asir-native-content/review.html`, `receipt.json`, `populated-engine.pdf` and `engine-validation.json`. Database-backed integration checks remain unavailable because the isolated report test schemas are absent; no application data was modified.
