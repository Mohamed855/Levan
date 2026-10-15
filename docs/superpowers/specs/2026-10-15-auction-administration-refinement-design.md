# Auction Administration Refinement Design

## Intent

Bring Tenant Auctions in line with the Assignments and Properties golden UI and backend contracts, without implementing the deferred generated-output download feature.

## Decisions

- Persisted auctions use only the active business lifecycle: `in_preparation`, `pending_approval`, `active`, `completed`, and `suspended`. `draft` remains exclusively a wizard-draft record, never an `auctions.status` value.
- The index filters are Select2 controls in this order: type, status, assignment, start date, end date, city. Filters target `#auctions-table` explicitly.
- The index displays the linked assignment instead of platform/venue and a text badge for status; it contains no activity toggle.
- Cover-image upload and persistence are removed. Generated template outputs remain deferred and are represented by a disabled/empty output state only until their generation feature is implemented.
- The create wizard uses assignment-style property selection, attachment boxes, review cards, and a live preview. Optional values render as `-` in review.
- Saving an auction always creates `pending_approval`; wizard drafts restore automatically without confirmation.

## Constraints

- Tenant actions continue to query only tenant models and retain existing policies/FormRequests/Actions.
- Validation is defined solely by Store/Update Auction FormRequests.
- File uploads keep server-side MIME/size validation and tenant-public storage.
- Database identifiers remain English; Arabic is display-only.
