# Auction establishment, approval and regulatory forms workflow

**Goal:** Implement the user's complete establishment-request management and two native PDF workflows, approval-gated closing, automatic lifecycle and separate marketing completeness.

**Execution:** Authorized implementation in the current checkout. Existing user edits to auction/closing DataTables and the progress-bar component must be preserved. Do not commit, push, switch branches or broadly format unrelated files.

## Binding requirements

- New tenant management: طلب إقامة مزاد. Its view action opens `auctions.show`; no standalone show page.
- Saving a request redirects to auction details and queues establishment PDF. No request PDF is created from auction creation alone.
- Request is under review until approved from a shared-component modal on auction details requiring approval number. Approval locks request edits and pins its data snapshot. Approval number is not entered in closing.
- Closing requires approved request plus approval number. Saving closing refreshes both current reports and the closing PDF; it never regenerates establishment implicitly.
- All actual linked properties sold means closed (nonempty collection). Closed auctions forbid auction/closing/marketing mutations; manual regeneration/download of existing documents remains allowed.
- Auction states: pending / approved / closed, independent of time and manual activation toggles. Completeness: incomplete / complete based on all configured campaign sections and required fields, not merely any campaign.
- Campaigns allowed in pending and approved, blocked in closed, including create/update/delete and batch paths.
- Tenant-specific queue jobs restore previous tenant context, use snapshots, reject stale revisions, private storage and permission-protected status/start/retry/download endpoints.

## Shared interfaces and ownership

1. **Lifecycle worker:** owns `Auction` lifecycle/relation (`establishmentRequest(): HasOne`), `AuctionStatus`, completeness service/enums/state and lifecycle guards in closing/marketing/auction updates; existing auction/closing/report DataTables and statistics/status displays. New model class below is owned by request worker. No routes/navigation edits.
2. **Request worker:** owns new `App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest` and its migration; request CRUD/index/edit/actions/controller/validation; approval action/request and its details partial. Routes prefix `auction-establishment-requests`, permission prefix `auction_establishment_requests`. No routes/navigation edits.
3. **Document worker:** owns new regulatory form data builder, generation model/migration/service/job/controller, details form-generation box + embedded polling script. No routes/navigation or `Auction` edits.
4. **Root:** routes, navigation, permissions, cross-module wiring, isolated test setup, integration/browser verification and final review.

### Request model contract

`auction_establishment_requests`: unique auction_id, data JSON (complete native template contract), submitted_at, approved_at nullable, approval_number nullable, created_by/updated_by/approved_by nullable, timestamps. `isApproved(): bool` requires approved_at and nonblank approval_number. Expose under_review/approved status for display. Never accept approval metadata via save payload. Source fields inherited from auction/property/company are assembled when saving; approved data stays immutable. Multiple property rows must carry stable property_id for closing alignment.

Request actions call `AuctionRegulatoryFormService::requestGeneration(Auction $auction, string $type): ?AuctionFormGeneration` after successful save/approval; type establishment. Approval number is copied into any legacy closing row only for compatibility, never requested from user there. Approval implementation may omit compatibility write if lifecycle worker reads the approved relation directly.

### Document contract

Namespace `App\Services\Tenants\Auctions\Forms`:
- `AuctionRegulatoryFormDataBuilder::build(Auction $auction, string $type): array`: establishment reads saved request data; closing overlays latest results on approved property metadata and includes approval number/date. No float casts for monetary values.
- `AuctionRegulatoryFormService::requestGeneration(Auction $auction, string $type): ?AuctionFormGeneration`, `startPendingGeneration(Auction $auction, string $type, string $tenantId): ?AuctionFormGeneration`, `retryLatest(Auction $auction, string $type): AuctionFormGeneration`, `latest(Auction $auction, string $type): ?AuctionFormGeneration`.
- `App\Models\Tenants\Auctions\AuctionFormGeneration`: auction_id, type, revision_token, status, progress, snapshot, file_path, error_message, started_at/completed_at, timestamps.
- Controller `App\Http\Controllers\Tenants\Auctions\AuctionRegulatoryFormsController`: `status`, `start`, `retry`, `download` with `(Auction $auction, string $type)`.
- Route names `auctions.forms.status/start/retry/download`, parameters auction/type. Partial `tenant.auctions.partials.regulatory-forms` included in details. Permission per type: request or closing `.view` for status/start/download and `.regenerate` for manual retry.
- Existing `AuctionRegulatoryForms::html` and injected `BrowserDocumentRenderer::pdf` produce native PDFs. Job shares auction report background connection/queue and worker startup helper.

Request controller methods index/edit/update/approve; method edit receives Auction and handles first creation or pending modification. Details partial `tenant.auctions.partials.establishment-request` handles approval modal and view/edit actions. Controller calls authorization, row locks and immutable snapshot checks on server.

## Tasks and evidence

- [x] Record failing lifecycle, request/approval and generation tests before implementation.
- [x] Implement each owned slice; preserve existing components/conventions and user edits.
- [x] Wire routes, menu, permissions and detail-page boxes.
- [x] Run migrations on explicitly isolated test schemas first; inspect local tenant and apply only the three additive workflow migrations (already installed).
- [x] Verify pending → review/save PDF → approved/approval number → closing save reports+PDF → all sold/closed; marketing permissions at each state.
- [x] Verify closed regeneration, old/stale jobs, permissions, tenant isolation, transaction locking and no pending-request closing bypass.
- [x] Render real native PDF examples and inspect details UI states, modal, DataTables and polling.
- [x] Independent review, scoped test/build checks and exact prompt audit.

## User stepper integration and review fixes

- Preserve the user's six-step establishment form and its JavaScript. Integrate its per-auction draft key with the shared draft system, authenticated upload validation, restored uploads on final PUT save, and successful-save cleanup.
- Reject draft uploads after approval and refuse using one auction's draft for another auction.
- Preserve approved property membership/assignment in auction updates. Validate approved-snapshot property parity inside closing's locked transaction before writing results.
- Permit establishment/closing viewers to use the shared auction details page; listing and changing auctions retain separate auction permissions.
- Verification evidence and remaining broader-suite limitations: `../verification/2026-11-09-auction-establishment-workflow.md`.

## Decisions

- Marketing completeness means every configured section has the required persisted fields under current campaign validation; optional fields stay optional. Do not assume a missing channel is complete.
- Historical closing data does not silently approve a new request; new closing writes require real approval. Preserve historical records and do not delete existing reports.
- Known template typography differences from the previous task remain documented; this workflow task does not assert new 100% pixel fidelity.
