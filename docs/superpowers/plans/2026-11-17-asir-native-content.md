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
- [ ] Run focused PHP/browser checks, style/syntax checks, and Haram regression checks. Review the final diff and record limitations honestly.

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
