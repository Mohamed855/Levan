# Trial Visibility, OTP Checkout, and Moyasar Testing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task after approval.

**Goal:** Hide the free/trial plan from the public site, preserve SuperAdmin selection, add a secure golden-pattern email OTP step before checkout details, and make Moyasar testing mode work without changing the project’s visual language.

**Architecture:** Add a single public-plan query boundary so public site pages exclude trial/free plans while SuperAdmin tenant creation keeps its current selectable-plan source. Split public checkout into a server-backed checkout session: validated customer data is stored temporarily, a fixed `000000` OTP is accepted only in testing mode, and only verified sessions may create the pending bill and render Moyasar. Reuse the existing Blade layouts, shared form controls, FormRequests, Actions/Services, and current Moyasar component.

**Tech Stack:** Laravel, Blade, Bootstrap 5, shared form validation, PHPUnit/Pest-style Laravel tests, Vite, Moyasar hosted form.

**Spec:** This plan is based on the approved user requirements in the conversation; no code implementation starts until this plan is approved.

## Global Constraints

- Public site must not display or accept the free/trial plan.
- SuperAdmin tenant creation must continue to select the free/trial plan.
- OTP is temporarily fixed to `000000` in testing mode only; production must not accept a fixed OTP.
- OTP verification must be server-side, time-limited, single-use, and scoped to the checkout session/email.
- Payment card data must remain inside Moyasar’s hosted form; never store or accept card fields locally.
- All submitted fields use FormRequest rules; no duplicated business validation in page JavaScript.
- Checkout views must use the existing site layout, shared form controls, and project typography/components.
- Any `.js`, `.css`, or `.scss` change requires `npm run build`.
- Do not remove the existing reCAPTCHA comments or re-enable reCAPTCHA in this task.

## Review Focus

- A trial plan must be absent from public pricing/checkout links but present in SuperAdmin tenant creation; covered by public-plan and admin-plan tests.
- An unverified email cannot create a bill or reach Moyasar; covered by checkout flow tests.
- Wrong, expired, reused, and missing OTP values must be rejected; covered by OTP tests.
- Refreshing or reopening the OTP step must preserve only the temporary checkout state and never expose the password; covered by session payload tests.
- Missing/invalid Moyasar test configuration must produce a project-standard error response rather than a blank page or uncaught exception; covered by Moyasar configuration tests.

### Task 1: Establish public versus administrative plan visibility

**Files:**
- Modify: `app/Models/Admin/Plans/Plan.php`
- Modify: `app/Http/Controllers/Site/LandingPageController.php`
- Modify: `app/Http/Controllers/Site/SubscriptionController.php`
- Modify: `resources/views/site/index.blade.php` and related public plan partials if needed
- Test: public plan visibility contract tests

- [ ] Add a named public-availability query scope/policy that excludes the free/trial plan using the project’s persisted plan identifier, while retaining active/available filtering.
- [ ] Use that boundary only in public landing and public checkout lookups; leave SuperAdmin company creation on its current `Plan::active()` source.
- [ ] Reject direct public checkout requests for the trial plan with the existing 404/standard unavailable-plan behavior.
- [ ] Add tests proving public exclusion and SuperAdmin inclusion.

### Task 2: Add the checkout verification state and OTP contract

**Files:**
- Create: `app/Http/Requests/Site/StartCheckoutVerificationRequest.php`
- Create: `app/Http/Requests/Site/VerifyCheckoutOtpRequest.php`
- Create: `app/Actions/Site/Checkout/StartCheckoutVerificationAction.php`
- Create: `app/Actions/Site/Checkout/VerifyCheckoutOtpAction.php`
- Create/modify: checkout session/OTP support under the existing Site/Shared service convention
- Test: OTP action and request contract tests

- [ ] Validate customer/billing data through the FormRequest, including the existing phone and password rules.
- [ ] Store only the minimum temporary checkout data; hash or encrypt the password and never expose it to the view/session response.
- [ ] Generate/store a verification identifier, email, expiry, attempt limit, and consumed state.
- [ ] In testing mode, accept exactly `000000`; keep the implementation behind configuration so production uses a generated OTP/mail delivery path.
- [ ] Make verification single-use and bind it to the checkout session and submitted email.

### Task 3: Implement the golden-pattern checkout screens

**Files:**
- Create/modify: `app/Http/Controllers/Site/SubscriptionController.php`
- Modify: `routes/site/site.php`
- Create: `resources/views/site/checkout/verify-email.blade.php`
- Create/modify: `resources/views/site/checkout.blade.php`
- Create/modify: `resources/views/site/moyasar-checkout.blade.php`
- Create/modify: relevant checkout JS only if needed
- Test: checkout feature tests

- [ ] Step 1 renders customer information as the primary form with plan summary separate from editable fields.
- [ ] Step 2 posts verified customer data to OTP verification and renders an OTP input with shared validation DOM attributes.
- [ ] Step 3 renders the existing plan/payment details and Moyasar hosted form only after successful verification.
- [ ] Preserve RTL layout, existing site cards/buttons, shared typography, responsive Bootstrap grid, CSRF, and Toastr/error handling.
- [ ] Add resend/back/expiry behavior only where supported by the existing architecture; do not add an unbounded resend loop.

### Task 4: Repair Moyasar test-mode integration

**Files:**
- Modify: `app/Services/shared/Payment/Moyasar/MoyasarFormConfiguration.php`
- Modify: `config/services.php` and `.env.example` if configuration keys are missing
- Modify: `resources/views/components/payment/moyasar-form.blade.php` if the existing component does not receive the correct test configuration
- Modify: `app/Http/Controllers/Site/SubscriptionController.php`
- Test: `tests/Feature/MoyasarRegistrationCheckoutFeatureTest.php` and configuration tests

- [ ] Ensure test mode reads `MOYASAR_MODE=test` and the test publishable key from configuration, never hardcoded secrets.
- [ ] Ensure the pending bill is created only after OTP verification and the view receives the correct amount/callback URL/test mode.
- [ ] Make missing/invalid test configuration return a clear project-standard error path and cancel any incomplete bill.
- [ ] Keep card details entirely in Moyasar’s hosted form and assert the rendered page contains no local card inputs.

### Task 5: End-to-end verification and cleanup

**Files:**
- Test/modify: relevant Unit and Feature tests
- Delete after implementation approval and completion: `docs/superpowers/plans/2026-10-18-trial-checkout-moyasar.md`

- [ ] Run focused public-plan, OTP, checkout, and Moyasar tests.
- [ ] Run `php artisan route:list --json`, `php artisan view:cache`, `npm run build`, and `git diff --check`.
- [ ] Run the full test suite with an extended timeout.
- [ ] Confirm no public route/view exposes the free/trial plan and SuperAdmin tenant creation still lists it.
- [ ] Delete this temporary plan file after the implementation is accepted, keeping only the project’s Golden Pattern markdown files as previously requested.
