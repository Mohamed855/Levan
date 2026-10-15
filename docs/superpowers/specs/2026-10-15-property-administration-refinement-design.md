# Property Administration Refinement Design

## Intent

Align the tenant property administration with the established assignment wizard pattern while preserving tenant isolation, draft recovery, and existing property records.

## Requirements

- Repair property index filters and prove each filter reaches the server-side DataTable.
- Make the location URL optional; make plan and parcel values, deed/identity/agency identifiers numeric inputs with server-side digit validation.
- Keep north, south, east, and west as the four default boundaries; allow the four diagonal directions as additional rows. Boundary type is a non-searchable Select2 with English values `street` and `parcel`.
- Preserve aerial image and boundary GeoJSON in tenant-scoped drafts, restoring both the image and polygon.
- Make owner-row errors visible through the shared form-validation contract.
- Replace the property-unit free-text type and usage values with English backed enums: `villa`, `apartment`, `shop`, `garden`, `other` and `residential`, `commercial`, `other`.
- Use the shared table classes for property units and initialize non-searchable Select2 controls for both initial and dynamically restored/added rows.
- Present additional gallery images and documents as assignment-style counted boxes in both the file step and review step.
- Add a review image row for the annotated aerial image, with a hover download action.

## Decisions

- Additional directions are the four compass diagonals: `northeast`, `northwest`, `southeast`, and `southwest`; they are never added automatically.
- Number-like identifiers remain string database columns to avoid losing leading zeros, but accept digits only and render with numeric inputs.
- Existing unit records are normalized through a tenant migration. Arabic legacy values map to the new English enum keys; unexpected legacy values become `other`.
- Draft aerial data is stored as draft data (not an uploaded attachment) and must survive the existing tenant-scoped drafts subsystem unchanged.

## Constraints

- Tenant boundary, authorization, and FormRequest validation remain the source of truth.
- No Arabic values are persisted as new database identifiers.
- Dynamic HTML is escaped before rendering; draft and uploaded files remain validated server-side.
- CSS/JS updates require `npm run build`.
