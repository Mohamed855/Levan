# Company Management Alignment Plan

## Confirmed scope

This plan applies only to the central Admin Companies resource and its archive lifecycle. It preserves the current public routes, submitted field names, tenant provisioning workflow, and existing company records.

Confirmed deletion contract:

- `DELETE /admin/companies/{company}` soft-deletes the tenant only. It must not delete subscriptions, bills, seats, or company settings.
- `DELETE /admin/archives/tenants/force-delete/{id}` permanently deletes the archived tenant and its related subscriptions and subscription records through the existing subscription deletion service.

## Target architecture

```text
Company create/edit Blade
    -> x-form.controls.* + x-form.layout.field + x-form.wizard.*
    -> shared form/wizard validation client
    -> WizardDraftController validation endpoint
    -> StoreCompanyRequest / UpdateCompanyRequest (sole validation source)
    -> CompanyService
    -> tenant, settings, bill, subscription, first tenant user
```

The current Admin Users visual wizard is the UX reference. Its page-local duplicated FormValidation rules are not copied. The shared component and FormRequest validation contract is the reference implementation.

## Phase 1 — Preserve business data and receipt files

### 1.1 Soft delete versus force delete

Files:

- `app/Services/Admin/Companies/CompanyService.php`
- `tests/Feature/CompanyAndSubscriptionRefactorTest.php`

Changes:

1. Change `deleteCompany()` to clear relevant cache and soft-delete only the tenant.
2. Keep permanent subscription cleanup exclusively in `forceDeleteCompany()`.
3. Verify restore returns the tenant with its original subscription data intact.
4. Retain `companies.delete`, `companies.restore`, and `companies.force_delete` authorization boundaries.

Acceptance criteria:

- Normal delete leaves central subscriptions, periods, seats, bills, and settings available for restore.
- Restore preserves the latest subscription and its status/periods.
- Force delete removes the archived tenant and subscriptions permanently.

### 1.2 Receipt persistence

Files:

- `app/Services/Admin/Companies/CompanyService.php`
- `app/Services/shared/Subscription/SubscriptionService.php` only if receipt ownership belongs there
- focused feature test(s)

Changes:

1. When the validated `receipt_upload` is supplied, store it on the approved central disk/path.
2. Pass its persisted path as `receipt_path` into the subscription/bill workflow.
3. Persist it in the bill `invoice_url`, which is already the downstream contract.
4. Delete a newly stored receipt if the surrounding company-creation workflow fails before completion.

Acceptance criteria:

- A paid bank-transfer creation stores an image/PDF receipt and its path is present on the created bill.
- A non-bank-transfer creation stores no receipt.
- No orphan file remains after a failed transaction/workflow.

## Phase 2 — Make FormRequests the validation authority

Files:

- `app/Http/Requests/Admin/Companies/StoreCompanyRequest.php`
- `app/Http/Requests/Admin/Companies/UpdateCompanyRequest.php`
- `resources/views/admin/companies/create.blade.php`
- `resources/views/admin/companies/edit.blade.php`
- shared validation endpoint/client only where required

Rules:

1. Keep all final rules, messages, attributes, and conditional business rules in the two FormRequests.
2. The frontend sends the actual form data to the existing FormRequest validation endpoint on input/change/blur and before a wizard transition; it does not reimplement Laravel rules in page JavaScript.
3. Keep the existing field names and request payload stable: `company_name`, `commercial_number`, `tax_number`, `city_id`, `address`, `name`, `phone`, `email`, `password`, `plan_id`, payment fields, and settings flags.
4. Retain server-side validation on final submit as the final authority.

Explicit rule decisions to implement:

- Mobile fields remain exactly `05xxxxxxxx` (10 digits) in both Store and Update requests.
- `commercial_number` remains a string, so leading zeroes are preserved; it gets numeric-only browser input behavior through the reusable control (`inputmode="numeric"`), not `type="number"`.
- Align email policy with the project standard used by Admin Users (`email:rfc,dns`) if the domain DNS requirement is confirmed for tenant-admin email. Otherwise retain the existing rule deliberately and document the exception.
- Make `trial_end_date` required when `payment_type=trial` if a trial cannot be created without an end date.
- Make `discount_value` required when `has_discount=yes`, with the existing non-negative decimal constraint.
- Resolve the payment-method contract: either add `cash` to the UI or remove it from the allowed backend enum. The plan assumes adding it so the UI represents the existing backend contract.
- Ensure file type, size, and conditional required errors are surfaced from the FormRequest.

## Phase 3 — Replace page-specific form code with reusable components

Files:

- `resources/views/admin/companies/create.blade.php`
- `resources/views/admin/companies/edit.blade.php`
- `resources/assets/js/admin/companies/create-wizard.js` (remove rule duplication; retain presentation-only behavior or remove if fully superseded)
- `resources/assets/js/shared/form-validation.js`
- `resources/js/shared/wizard/create-wizard-enhancer.js` only for supported hooks

Changes:

1. Build create/edit fields from the existing `x-form.controls.input`, `textarea`, `select`, `password`, and `image-upload`-style contracts together with `x-form.layout.field`.
2. Use `x-form.wizard.stepper`, steps, and actions for create. Each step declares its actual field names.
3. Add `data-form-validate` and field/control validation attributes to every submitted field, including Select2, checkboxes, numeric text fields, and file inputs.
4. Use one wizard engine. Register supported `wizardCreateHooks` for company-only presentation behavior such as updating the review screen; remove competing click handlers and all page-level copies of Laravel validation rules.
5. Keep only presentation logic in a company-specific script: plan display/end-date helper, conditional visibility of payment controls, and review-summary rendering. It must not decide validity.
6. Add immediate validation to the edit form through the shared client, which it currently lacks.

Compatibility guarantees:

- Same routes, methods, CSRF fields, multipart encoding, names, old input values, and server error behavior.
- Existing draft resource key (`admin.company-create`) and draft restoration remain usable.
- Existing Select2 and Bootstrap stepper assets remain loaded.

## Phase 4 — DataTable, actions, and safe errors

Files:

- `app/DataTables/Admin/Companies/CompanyDataTable.php`
- `app/Http/Controllers/Admin/Companies/CompanyController.php`
- `resources/views/admin/companies/action.blade.php` only if authorization markup needs adjustment
- focused feature tests

Changes:

1. Show the add-company DataTable button only to `companies.create`.
2. Preserve the existing non-toggle company status display; no status toggle is added because no status-change route/action is in scope.
3. Keep a dashboard link only under the intended authorization rule; if no dedicated permission exists, apply the agreed existing view permission.
4. Log/report unexpected store failures but show a generic Arabic flash message, never the exception message.

## Phase 5 — Verification

Backend feature coverage:

1. Authorization for view/create/show/edit/delete/restore/force delete.
2. All Store/Update request validation, including 05 mobile format and conditional payment rules.
3. Normal delete + restore retains subscription data.
4. Force delete removes tenant and subscription records.
5. Bank transfer receipt is stored and assigned to the bill; unsupported/oversized receipt is rejected.
6. DataTable create button visibility by permission.

Frontend/manual coverage:

1. Every field produces immediate FormRequest-backed feedback.
2. Select2, files, checkboxes, conditional payment controls, decimals, and date dependencies validate correctly.
3. Draft save/restore, all four steps, review summary, final submit, and edit form work with no duplicate event handlers.

Required commands after implementation:

1. Focused PHPUnit feature tests (database availability permitting).
2. `php artisan view:cache`.
3. `npm run build` after JavaScript/Blade asset changes.
4. `git diff --check`.

## Explicitly out of scope

- Redesigning company pricing or subscription status business rules beyond the listed validation inconsistencies.
- Adding an inline company status toggle.
- Changing database keys, permission names, tenant identifiers, or existing route names.
