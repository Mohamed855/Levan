> Execution completed on 2026-11-06. See [acceptance and verification](../reports/2026-11-06-auction-report-cycle-acceptance.md) and `output/cycle-review/progress.md` for implementation rulings and the verification actually run. The detailed checklists below preserve the original pre-implementation plan; actual class/file organization and concurrency/isolation verification adaptations are recorded in the delivery report.
# Auction Report Cycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Use checkbox steps for progress. Do not commit, create a worktree, or deploy without applicable session authorization.

**Goal:** Implement the approved company identity sources, campaign CRUD, auction closing, conditional PDFs, report index, finalization locks, and report refresh behavior.

**Architecture:** Keep the existing tenant-scoped Laravel/Blade/Yajra module pattern and current dual-PDF renderer. Store campaigns independently, closing once per auction, and results per auction/property. Keep generation records as technical jobs; present two stable logical report rows per auction and publish only a successfully generated PDF pair.

**Tech Stack:** Existing Laravel 12, PHP 8.2+, Blade shared form/wizard/DataTable components, existing JS stack, Stancl tenancy, Spatie permissions, Yajra DataTables, current browser PDF renderer and Poppler. No new runtime dependency.

**Spec:** [Approved-scope design](../specs/2026-11-05-auction-report-cycle-design.md). It includes one explicitly marked interpretation: incomplete without a valid saved campaign, complete with valid auction data and a saved campaign; optional channel pages do not imply mandatory channels.

## Global Constraints

- Existing platform components, colors and layouts; wireframe supplies data and behavior only.
- Do not implement or refactor the files/images management subsystem.
- Use tenant logo and issuing entity logo; office_name, logo_text and issuing entity name are not report inputs.
- Auction platform name/URL remain auction data.
- Tenant settings changes never auto-regenerate PDFs; explicit regeneration uses current tenant logo/contact settings.
- Marketing has a normal create button; choose auction first, type second; multiple campaigns including repeated types allowed.
- Closing index includes every auction and has no global create button.
- Campaign/closing save redirects to auction details and requests refreshed reports.
- Only saved campaign sections and saved closing sections appear.
- Exactly two logical report-index rows per auction regardless of generation count.
- Ended means saved closing, at least one property, and every property sold.
- Only auction/campaign/closing records are locked after ending; other source entities remain editable.
- Finished business data remains in its snapshot; manual regeneration overlays current tenant settings.
- Preserve the last successful PDF pair until its replacement pair is published.
- New or changed PHP APIs must use installed library documentation when implementation requires API verification.
- Do not run Feature tests against the current default database configured in phpunit.xml.

## Review Focus

1. A zero statistic is supplied data, unlike null or an absent channel: tested in Tasks 3 and 5.
2. A campaign moved between auctions must refresh both and cannot enter/leave a finalized auction: tested in Task 3.
3. Settings updates leave generation count unchanged, but manual regeneration includes the latest logo/contact values: tested in Tasks 2 and 7.
4. A stale or failed PDF job cannot remove or overwrite the latest successful pair: tested in Task 7.
5. Lock checks must protect direct requests, bulk deletion, toggles, and concurrent updates, while shared entities stay editable: tested in Tasks 4 and 6.

## Existing references and baseline

- Active auction form: resources/views/tenant/auctions/partials/_form.blade.php. Results and campaign steps are commented out; do not re-enable the old combined UX.
- CRUD reference: AssignmentsController, AssignmentsDataTable and resources/views/tenant/assignments/.
- Rendering: AuctionReportDataBuilder -> AuctionReportRenderer -> HaramReportData/AsirReportData -> AuctionReportArtwork.
- Publication: AuctionReportService + GenerateAuctionReportsJob; retry currently reuses a snapshot and clears paths before success, which must change.
- Pure-unit baseline run on 2026-11-05: 22 tests, 132 assertions passed for AuctionReportPaginatorTest and AuctionReportArtworkTest.
- DB Feature-test guard requires central levan_report_ci_central, tenant levan_report_ci_tenant, tenant ID auction_report_ci, empty DB_URL. Use scripts/auction-reports/prepare-isolated-tests.php only after inspecting its setup and using disposable test resources.

## Task 1 — Data model foundation and legacy compatibility

**Create:**
- database/migrations/tenant/2026_11_05_120000_add_report_contacts_to_settings.php
- database/migrations/tenant/2026_11_05_120100_create_auction_marketing_campaigns.php
- database/migrations/tenant/2026_11_05_120200_create_auction_closings.php
- database/migrations/tenant/2026_11_05_120300_add_auction_closing_and_publication_fields.php
- app/Models/Tenants/MarketingCampaigns/MarketingCampaign.php
- app/Models/Tenants/AuctionClosings/AuctionClosing.php
- tests/Feature/Tenants/Auctions/AuctionReportCycleSchemaTest.php

**Modify:** Settings.php, Auction.php, AuctionAttachment.php under their current model folders.

**Interfaces/data:**
- Campaign: auction_id, channel, platform_label, metrics (known-key JSON), url, link_label, link_role, created_by; timestamps. Multiple per auction/channel.
- Campaign evidence: nullable marketing_campaign_id on existing AuctionAttachment. Preserve existing evidence that has no campaign owner.
- Closing: unique auction_id, closing_date, approval_number, saved_at, created_by/updated_by, finalized_snapshot; timestamps.
- Pivot: buyer_name and result_notes alongside existing result fields.
- Auction: published_report_generation_id pointer. Keep current generation table for processing status and full snapshots.
- Settings: report_phone, report_unified_phone, report_social; nullable for existing tenants.
- Auction::marketingCampaigns(), Auction::closing(), Auction::publishedReportGeneration().

- [ ] Write failing schema tests: two campaigns of same channel persist; one closing per auction; JSON preserves 0/null; campaign evidence cannot refer to another tenant; old snapshot data stays readable.
- [ ] Run only the isolated Feature test; confirm expected missing schema/model failure.
- [ ] Implement additive schema and relationships; no broad deletion or arbitrary contact migration.
- [ ] Backfill legacy marketing_data.campaigns once with stable provenance to prevent duplicate import. Preserve legacy evidence without guessing ownership when several campaigns share its channel.
- [ ] Verify migration on isolated databases and rerun schema tests.

## Task 2 — Settings contacts, snapshot sources and dynamic logos

**Modify:**
- app/Http/Requests/Tenants/Settings/MainSettingsRequest.php
- app/Actions/Tenants/Settings/UpdateTenantSettingsAction.php
- resources/views/tenant/settings/settings.blade.php
- resources/views/tenant/auctions/partials/steps/step4-reports-attachments.blade.php
- resources/views/tenant/auctions/partials/steps/step7-review.blade.php
- app/Http/Requests/Concerns/AuctionReportDetailRules.php
- app/Http/Requests/Tenants/Auctions/{StoreAuctionRequest,UpdateAuctionRequest}.php
- app/Services/Tenants/Auctions/Reports/{AuctionReportDataBuilder,HaramReportData,AsirReportData,AuctionReportArtwork}.php
- resources/auction-reports/{haram,asir}/bindings.php

**Create:**
- app/Services/Tenants/Auctions/Reports/AuctionReportBranding.php
- tests/Unit/Auctions/AuctionReportBrandingTest.php
- tests/Feature/Tenants/Auctions/AuctionReportCompanySourcesTest.php

**Interfaces:**
- AuctionReportBranding::current(): array returns company_logo plus phone/unified_phone/social as tenant-local values/data URIs.
- AuctionReportBranding::apply(array $snapshot): array overlays current tenant settings without replacing frozen business sources.
- Snapshot identity includes company_logo and issuing_entity_logo from assignment.issuingEntity, not assignmentImage.
- Artwork receives explicit logo slots for each source position. Keep source artwork appearance outside those positions.

- [ ] Write failing tests for two different tenant logos, issuing entity logo source, settings contacts, excluded names, and absent-logo neutral behavior.
- [ ] Run the focused tests and verify failures.
- [ ] Add settings inputs/validation/persistence and remove auction contact controls and accepted writes.
- [ ] Map every static Levan/Infath position in the original artwork, including raster-background positions in Haram. Replace only the intended logo region; verify all template pages and continuations.
- [ ] Use the tenant/issuing logo sources in snapshots and both PDF data adapters.
- [ ] Verify updating settings alone does not request generation.
- [ ] Rerun focused tests; render controlled fixtures for visual logo inspection.

## Task 3 — Campaign CRUD and auction-details integration

**Create:**
- app/Http/Controllers/Tenants/MarketingCampaigns/MarketingCampaignsController.php
- app/DataTables/Tenants/MarketingCampaigns/MarketingCampaignsDataTable.php
- app/Http/Requests/Tenants/MarketingCampaigns/{StoreMarketingCampaignRequest,UpdateMarketingCampaignRequest}.php
- app/Actions/Tenants/MarketingCampaigns/{CreateMarketingCampaignAction,UpdateMarketingCampaignAction,DeleteMarketingCampaignAction}.php
- app/Policies/Tenants/MarketingCampaigns/MarketingCampaignPolicy.php
- resources/views/tenant/marketing-campaigns/{index,create,edit,action}.blade.php
- resources/views/tenant/marketing-campaigns/partials/_form.blade.php
- resources/assets/js/tenant/marketing-campaigns/create.js
- config/auction-marketing-campaigns.php
- tests/Feature/Tenants/MarketingCampaigns/MarketingCampaignWorkflowTest.php

**Modify:** routes/tenant.php, resources/views/tenant/auctions/show.blade.php.

**Interfaces/behavior:**
- Routes marketing-campaigns resource with tenant-bound models and permissions.
- Config maps existing supported channels to relevant existing metrics and evidence sections; no requirement that every channel be used.
- Valid campaign requires an accessible editable auction, a supported channel, and actual campaign detail (metric including zero, valid URL, or supported evidence). No invented defaults.
- The form uses auction/type selection first, type-specific detail controls, evidence, and review.
- Actions call the refresh service from Task 7 after successful persistence, once per affected auction.
- Editing auction_id refreshes both old and new owners; disallow reassignment involving a finalized auction.
- Index queries saved campaigns; has global create; no virtual empty campaign per auction.

- [ ] Write failing HTTP tests for create/edit/delete, multiple same-channel campaigns, type-specific validation, forged auction IDs, and 0 vs null.
- [ ] Verify the tests fail for missing routes/features.
- [ ] Implement request/action/policy and Blade/DataTable flow using current components.
- [ ] Add permission-controlled campaigns table to auction details; save/delete redirect to corresponding details.
- [ ] Test reassignment refreshes old and new auctions and checks both locks.
- [ ] Verify no changes to the media library or property image editor.

## Task 4 — Auction closing workflow

**Create:**
- app/Http/Controllers/Tenants/AuctionClosings/AuctionClosingsController.php
- app/DataTables/Tenants/AuctionClosings/AuctionClosingsDataTable.php
- app/Http/Requests/Tenants/AuctionClosings/SaveAuctionClosingRequest.php
- app/Actions/Tenants/AuctionClosings/SaveAuctionClosingAction.php
- app/Policies/Tenants/AuctionClosings/AuctionClosingPolicy.php
- resources/views/tenant/auction-closings/{index,edit,action}.blade.php
- resources/views/tenant/auction-closings/partials/_form.blade.php
- resources/assets/js/tenant/auction-closings/edit.js
- tests/Feature/Tenants/AuctionClosings/AuctionClosingWorkflowTest.php

**Modify:** routes/tenant.php and auction details with read-only closing summary.

**Interfaces/behavior:**
- Index is backed by Auction with its optional closing; all auctions appear at creation.
- Row action opens input/review or read-only final state; no top create.
- SaveAuctionClosingAction::execute(Auction $auction, array $data, int $actorId): AuctionClosing.
- Lock the auction in a transaction, validate property ownership, persist closing metadata and each property result.
- Sold requires buyer_name and sale_price; unsold requires unsold_reason. Result notes remain distinct from board_notes.
- Preserve existing opening prices/participant/bid values unless explicitly edited.
- Mark finalization only for a nonempty fully sold property set and freeze business snapshot inside the same saved lifecycle.
- Redirect to auction details and request reports after commit.

- [ ] Write failing tests for initial index, mixed sold/unsold closing, required fields, foreign properties, empty-property finalization, and fully sold finalization.
- [ ] Run and inspect the expected failures.
- [ ] Implement the current component-based wizard/review and transaction action.
- [ ] Test simultaneous closing/update attempts against the same auction.
- [ ] Verify no independent approval reviewer, Word export or new media management is introduced.

## Task 5 — Conditional PDF content per saved campaign and closing

**Modify:**
- app/Services/Tenants/Auctions/Reports/{AuctionReportDataBuilder,AuctionReportPaginator,HaramReportData,AsirReportData,AuctionReportEvidencePage}.php
- resources/auction-reports/{haram,asir}/bindings.php where data placeholders require targeted removal
- tests/Unit/Auctions/{AuctionReportPaginatorTest,AuctionReportRendererTest,AuctionReportChannelsTest}.php
- tests/Feature/Tenants/Auctions/AuctionReportGenerationTest.php

**Snapshot contract:**
- campaigns is the normalized saved campaign list, with campaign-owned metrics, URLs and evidence.
- closing_saved explicitly distinguishes no closing from saved results; do not infer it from ended time.
- Frozen finalized snapshots remain self-contained.
- Generic auction identity/introduction remains available; absent marketing/closing subsections do not output empty native pages.

- [ ] Write failing assertions: no campaigns -> no campaign pages; one X -> only X campaign pages; two X -> two distinguishable campaign bodies; no closing -> no sold/unsold result pages.
- [ ] Add cases for zero statistics, deleted last campaign, other channel, PDFs with multiple evidence pages, and saved mixed results.
- [ ] Run red tests.
- [ ] Make pagination select saved content and pass each campaign context into adapters instead of mixing metrics or URLs across campaigns.
- [ ] Suppress empty marketing-only sections, empty evidence placeholders and default closing results; retain provided evidence without implementing a new attachment concept.
- [ ] Verify page numbering and continuation layouts still work for large data.
- [ ] Rerun focused paginator/renderer/media tests and compare both PDFs visually.

## Task 6 — Report index, content states, and finalization guards

**Create:**
- app/Enums/Tenants/Auctions/AuctionReportContentStatus.php
- app/Services/Tenants/Auctions/Reports/AuctionReportState.php
- app/DataTables/Tenants/Auctions/AuctionReportsDataTable.php
- app/Http/Controllers/Tenants/Auctions/AuctionReportsController.php
- app/Services/Tenants/Auctions/Reports/AuctionReportLock.php
- resources/views/tenant/auction-reports/{index,action}.blade.php
- tests/Feature/Tenants/Auctions/AuctionReportIndexTest.php
- tests/Feature/Tenants/Auctions/AuctionReportFinalizationLockTest.php

**Modify:**
- app/Policies/Tenants/Auctions/AuctionPolicy.php
- app/Actions/Tenants/Auctions/UpdateAuctionAction.php
- app/Services/Tenants/Auctions/AuctionService.php
- app/Actions/Tenants/MassDelete/DeleteTenantRecordsAction.php
- app/Http/Requests/Tenants/Auctions/RetryAuctionReportsRequest.php
- routes/tenant.php

**Interfaces:**
- AuctionReportState::forAuction(Auction $auction): AuctionReportContentStatus.
- AuctionReportLock::assertEditable(Auction $auction): void throws the existing project's validation/authorization exception style.
- Report index query expands each auction into outputs/report kinds and joins its published/latest generation metadata; stable row key = auction ID + kind.
- States: incomplete without valid campaigns/base; complete with valid saved data/campaigns; ended after saved all-sold closing.
- Regenerate permission is separate from permission to mutate locked business data.

- [ ] Write failing tests asserting 2 rows after creation and still 2 after repeated generation; filter/search permissions and correct type download.
- [ ] Write state-transition tests including delete-last-campaign and final-state precedence.
- [ ] Test direct update, status toggle, delete/bulk delete, campaign changes and closing changes are rejected after finalization.
- [ ] Test shared settings/property/assignment/issuing-entity editing remains allowed by report lock; existing unrelated service constraints remain intact.
- [ ] Implement query/index and server-side guard reuse in all report-business mutation entry points.
- [ ] Verify hidden/disabled UI actions match server permissions; explicit regenerate still allowed.

## Task 7 — Mutation refresh, manual regeneration and atomic publication

**Create:**
- app/Services/Tenants/Auctions/Reports/AuctionReportRefreshService.php
- tests/Feature/Tenants/Auctions/AuctionReportRefreshLifecycleTest.php
- tests/Feature/Tenants/Auctions/AuctionReportPublicationTest.php

**Modify:**
- app/Services/Tenants/Auctions/Reports/AuctionReportService.php
- app/Jobs/Tenants/Auctions/GenerateAuctionReportsJob.php
- app/Http/Controllers/Tenants/Auctions/AuctionReportController.php
- app/Actions/Tenants/Auctions/{CreateAuctionAction,UpdateAuctionAction}.php
- app/Actions/Tenants/Properties/UpdatePropertyAction.php
- app/Actions/Tenants/Assignments/UpdateAssignmentAction.php
- app/Services/Tenants/Assignments/IssuingEntityService.php
- app/Services/Tenants/Properties/PropertyService.php only for existing allowed mutations that affect report sources
- resources/views/tenant/auctions/show.blade.php
- app/DataTables/Tenants/Auctions/AuctionsDataTable.php

**Interfaces:**
- AuctionReportRefreshService::afterMutation(array $auctionIds): void deduplicates tenant-local IDs and schedules fresh snapshots after commit.
- AuctionReportService::regenerate(Auction $auction, string $tenantId): AuctionReportGeneration creates a new technical attempt without destroying published files.
- Nonfinal manual generation rebuilds live business sources + current tenant settings.
- Final manual generation uses finalized business snapshot + AuctionReportBranding::apply for live settings.
- Publish: require both PDFs, compare latest revision under lock, update published pointer, then delete prior published file paths.
- Failure/stale attempt never deletes the last published pair.
- Existing download/status responses expose current generation progress and published availability separately.

- [ ] Write red tests for each mutation type, no-op changes, transaction rollback, campaign deletion/reassignment, and setting-only changes.
- [ ] Test current tenant logo/contact overlay on manual regeneration, including after finalization; property business changes do not rewrite the frozen final snapshot.
- [ ] Test job failure between the two files preserves last pair; latest generation wins; stale completion is ignored.
- [ ] Implement after-commit refresh wiring only on report-affecting mutations; avoid model callbacks that queue before attachments/pivots are finished.
- [ ] Preserve no-auto-refresh on settings; replace the old retry behavior that clears current paths before success.
- [ ] Publish and clean old files only within the target tenant/auction; authorized replacement does not extend to unrelated files.
- [ ] Rerun existing queue/isolation/download suites plus lifecycle/publication tests.

## Task 8 — Navigation, permissions, acceptance and final scope audit

**Modify:**
- resources/views/tenant/layouts/sections/menu/verticalMenu.blade.php
- database/seeders/tenant/PermissionsRolesSeeder.php
- app/Providers/AuthServiceProvider.php
- lang/ar/auction_reports.php
- Add an idempotent tenant permission upgrade migration following installed permission conventions for existing tenants.

**Create:**
- tests/Feature/Tenants/Auctions/AuctionReportCycleAcceptanceTest.php
- output/cycle-review/11-existing-module-data-audit.md

**Permission names:** marketing_campaigns.view/create/edit/delete, auction_closings.view/create/edit, auction_reports.view. Keep existing auction download/regenerate permissions interoperable.

- [ ] Add focused permission/menu tests before wiring.
- [ ] Wire menus and policy registration using current components. Give existing Admin role the new permissions without resetting customized roles.
- [ ] Complete the data-only audit of property/assignment/auction fields against wireframes; distinguish required report fixes from unapproved extras.
- [ ] Exercise end-to-end: create auction -> two report rows -> add two campaigns -> update/delete one -> save mixed closing -> save all-sold closing -> confirm locks -> change tenant logo -> manual regenerate with new logo.
- [ ] Test the same flow in a second tenant to verify isolation and authorization boundaries.
- [ ] Run PHP lint on changed PHP, targeted Pint, focused PHP/JS tests, and npm run build if frontend assets changed.
- [ ] Run the affected report suite on explicitly isolated DBs, not the default application database.
- [ ] Render actual fixture PDFs and inspect every logo position, absent campaign/result sections, duplicate campaign sections, continuations, and contact links.
- [ ] Review diffs with clean-code-and-review, code-simplifier and security-guidance; avoid unrelated refactors.
- [ ] Verify every user requirement against the spec; report tests actually run and any external-environment limitation. Do not claim the feature complete with a pending required path.

## Execution gate

This plan is for review before production code edits, following writing-plans. No database migration, code implementation, worktree creation or deployment has occurred in preparation of this plan.

