# Central Public Requests Design

## Intent

Replace the public website's Etmam submission flow with local Central Database persistence and add two Superadmin administrations: contact requests and trial requests.

## Scope

- `contact_requests` administration: list, show, reply through textarea, send reply email, and transition the request to `replied`.
- `trial_requests` administration: list, show, approve, reject with a required reason, and open the existing company wizard with request data prefilled.
- Add a seven-day trial plan through the existing admin plan seeding pattern.
- Remove the Etmam connector/service usage and leftover public-request integration code.
- Use existing DataTable, form, modal, wizard, validation, toast, and mail-job patterns.

## Boundaries and identifiers

- Boundary: `central` / Superadmin.
- Database: central connection only.
- Route namespace: `admin.contact-requests.*` and `admin.trial-requests.*`.
- Permissions: `contact_requests.view`, `contact_requests.reply`, `trial_requests.view`, `trial_requests.approve`, `trial_requests.reject`.
- Workflow keys: `pending`, `replied`, `approved`, `rejected`.
- Arabic labels remain presentation/localization text only.

## Request lifecycle

### Contact

Public POST validates through `StoreContactRequest`, then a Central action persists the request as `pending`. The Superadmin show page displays the original message and a textarea reply modal. A typed reply request invokes a reply action in a transaction, stores the reply and `replied_at` metadata, dispatches the existing mail-job pattern, and changes the status to `replied` only after the local operation succeeds.

### Trial

Public POST validates through `StoreTrialRequest`, then a Central action persists the request as `pending`. The show page offers approve and reject actions. Reject requires a reason, stores it, and changes status to `rejected`. Approve changes status to `approved` and redirects to the existing company-create wizard with `customer_name`, `customer_email`, and `customer_phone` prefilled into the first admin user fields. Company-only fields remain editable and required. The request must not be approved twice.

## Golden Pattern manifest

Each administration includes routes, `auth:admin` and permission middleware, controller, typed FormRequests, policy, action/service, central model and enum, DataTable, index/show UI, existing shared components and JavaScript contracts, mail job where applicable, and Feature/Unit tests. Any non-applicable layer will be recorded explicitly as `not_applicable` with a reason in the manifest.

## Trial plan

Add an idempotent seeded Central plan using the existing `Plan` schema and seeder conventions. It is active, limited, uses a seven-day duration, and has an English database code suitable for lookup. Its remaining limits and display metadata follow the project's existing default plan conventions rather than introducing a second plan schema.

## Non-goals

- No Etmam API calls, credentials, connector, fallback, or integration state for these requests.
- No Tenant-side request administration.
- No rich text editor or attachments for contact replies.
- No new generic repository layer.

## Acceptance criteria

- Public Contact and Trial submissions persist locally in Central DB and never call Etmam.
- Validation remains defined by FormRequests and shared frontend validation only orchestrates it.
- Unauthorized Superadmin users receive 403 for each protected action.
- Contact reply requires textarea content, sends the customer email, and ends in `replied`.
- Trial rejection requires a reason and ends in `rejected`.
- Trial approval is idempotent and opens the existing company wizard with the agreed three-field mapping.
- Seven-day trial plan is inserted or updated idempotently by the seeder.
- Focused Feature, Unit, policy, and integration-removal tests pass; frontend build runs after JS/CSS changes.
