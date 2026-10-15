# Auction Administration Refinement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Align tenant auction listing and wizard creation with the golden Assignment pattern and remove the persisted draft auction lifecycle.

**Architecture:** Keep controllers, policies, FormRequests and Actions as the mutation boundary. Update the existing Auction enum/action/service/DataTable and reuse the shared wizard/draft, Select2 and Assignment UI contracts.

**Tech Stack:** Laravel, Blade, Yajra DataTables, jQuery/Select2, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-15-auction-administration-refinement-design.md`

## Global Constraints

- Tenant data remains tenant-scoped and authorization remains policy/FormRequest based.
- FormRequests are validation source of truth; database values are English keys.
- Run `npm run build` after JS/CSS changes.

## Review Focus

- A final auction submission cannot persist `draft`.
- Assignment/property relationship validation rejects a property outside the selected assignment.
- A restored wizard draft does not display a restore/ignore modal.
- Empty optional review values display `-`.
- Attachment and output empty states do not create false download links.

### Task 1: Lifecycle and index contracts

**Files:** Enum, Create/Update Auction Actions, AuctionService, AuctionsDataTable, index view, auction feature tests.

- [ ] Write failing tests for non-draft creation, assignment/status/date/city filters, assignment column and text status badge.
- [ ] Remove `draft` from persisted status choices and normalize existing draft records through a tenant migration.
- [ ] Make final creation default to `pending_approval`; remove index toggle and platform column; add ordered filters.
- [ ] Run focused auction tests.

### Task 2: Wizard input and draft contracts

**Files:** Store/UpdateAuctionRequest, create Blade/JS, wizard shared draft behavior, auction feature tests.

- [ ] Write failing tests for required description/count/platform/start/end times and cover-image absence.
- [ ] Make the six basic fields use `col-lg-6`, require the requested values, and remove cover-image storage.
- [ ] Restore auction drafts automatically and render selected properties with the assignment selection component contract.
- [ ] Run focused auction tests.

### Task 3: Attachments and canonical review

**Files:** auction create Blade/JS and focused feature tests.

- [ ] Write failing DOM contracts for two attachment boxes and assignment-style review card IDs.
- [ ] Add counted attachment boxes, archive-aware restoration, removable selected files, and review cards with `-` fallbacks.
- [ ] Keep outputs visibly deferred rather than inventing downloads.
- [ ] Run focused tests and build assets.

### Task 4: Verification

- [ ] Review the complete diff for tenant authorization, file validation, and escaped client rendering.
- [ ] Run auction-focused tests, relevant contracts, `npm run build`, and the project suite; report unrelated existing failures by name.
