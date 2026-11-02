# Password Complexity Policy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce one password policy everywhere the application creates, resets, or changes a user password: at least eight characters, one uppercase English letter, one digit, and one symbol.

**Architecture:** Configure Laravel's `Password::defaults()` once in `AppServiceProvider`, adding the uppercase requirement as an additional rule to Laravel's native minimum-length, number, and symbol checks. Replace every manually specified `min:8` password rule with `Password::defaults()` so all request types share the same policy while preserving their existing required/nullable/confirmed behavior.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11.

**Spec:** User request in this conversation: make password validation a general rule and apply it to every password-creation path.

## Global Constraints

- Keep the existing eight-character minimum and confirmation behavior.
- Require an uppercase English letter, a digit, and a symbol; do not introduce a lowercase-letter requirement.
- Modify only password-validation behavior and its tests; preserve the user's existing uncommitted view changes.
- Use server-side Laravel validation as the source of truth.

## Review Focus

- A password with lowercase letters, a digit, and a symbol but no uppercase letter must be rejected.
- A password with uppercase letters and a symbol but no digit must be rejected.
- A password with uppercase letters and a digit but no symbol must be rejected.
- A compliant eight-character password must pass.
- Optional password fields must remain valid when omitted, but be subject to the shared policy when supplied.

---

### Task 1: Define and prove the shared password default

**Files:**

- Create: `tests/Unit/PasswordComplexityPolicyTest.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**

- Produces: `Password::defaults()` configured for application-wide password validation.

- [ ] **Step 1: Write failing policy tests**

Use `Validator::make()` with `Password::defaults()` to prove a compliant password passes and passwords missing an uppercase letter, digit, or symbol each fail.

- [ ] **Step 2: Run the new test to verify it fails**

Run: `php artisan test tests/Unit/PasswordComplexityPolicyTest.php`

Expected: the missing complexity requirements are accepted before the shared default is configured.

- [ ] **Step 3: Configure the Laravel password default in `AppServiceProvider::boot()`**

Set `Password::defaults()` to `Password::min(8)->numbers()->symbols()->rules('regex:/[A-Z]/')`, importing `Illuminate\Validation\Rules\Password`.

- [ ] **Step 4: Run the policy test to verify it passes**

Run: `php artisan test tests/Unit/PasswordComplexityPolicyTest.php`

Expected: PASS.

### Task 2: Route every manual password rule through the shared default

**Files:**

- Modify: `app/Actions/Fortify/PasswordValidationRules.php`
- Modify: `app/Http/Controllers/Tenants/Auth/PasswordChangeController.php`
- Modify: `app/Http/Requests/Admin/AdminMangment/Users/AdminUserStoreRequest.php`
- Modify: `app/Http/Requests/Admin/AdminMangment/Users/AdminUserUpdateRequest.php`
- Modify: `app/Http/Requests/Admin/Clients/StoreClientRequest.php`
- Modify: `app/Http/Requests/Admin/SubscriptionManagement/FinalizeCompanySubscriptionRequest.php`
- Modify: `app/Http/Requests/Admin/SubscriptionManagement/StoreSubscriptionRequest.php`
- Modify: `app/Http/Requests/Site/SubscriptionCheckoutRequest.php`
- Modify: `app/Http/Requests/Tenants/UserManagement/User/StoreUserRequest.php`
- Modify: `app/Http/Requests/Tenants/UserManagement/User/UpdateUserRequest.php`
- Test: `tests/Unit/PasswordComplexityPolicyTest.php`

**Interfaces:**

- Consumes: globally configured `Password::defaults()` from Task 1.
- Produces: every password-creation, reset, and change validator delegates complexity checks to that shared default.

- [ ] **Step 1: Extend the failing test with request-rule coverage**

Instantiate each affected FormRequest and assert its supplied password rules reject a password without each required complexity category; assert nullable update fields remain valid when absent.

- [ ] **Step 2: Run the new coverage to verify it fails**

Run: `php artisan test tests/Unit/PasswordComplexityPolicyTest.php`

Expected: at least one manual `min:8` rule accepts an incomplete password.

- [ ] **Step 3: Replace manual password complexity definitions**

Import `Illuminate\Validation\Rules\Password` where needed and substitute `Password::defaults()` for the duplicated `min:8` rule, retaining each field's existing `required`, `nullable`, `string`, `max`, and `confirmed` rules. For `StoreSubscriptionRequest`, express the existing conditional required/nullable behavior as rule arrays before appending `Password::defaults()`.

- [ ] **Step 4: Move the tenant controller's inline validation to an existing or dedicated FormRequest if needed**

Keep the controller focused on handling a validated request; ensure `new_password` applies the shared default without changing tenant route behavior.

- [ ] **Step 5: Run focused tests to verify they pass**

Run: `php artisan test tests/Unit/PasswordComplexityPolicyTest.php tests/Feature/AccountPasswordChangeTest.php tests/Feature/TenantPasswordResetOtpFeatureTest.php tests/Feature/AdminClientsFeatureTest.php`

Expected: PASS.

### Task 3: Verify the complete password-validation surface

**Files:**

- Test: `tests/Unit/PasswordComplexityPolicyTest.php`

- [ ] **Step 1: Scan production PHP for password rules outside the shared default**

Run: `rg -n --glob '*.php' --glob '!vendor/**' 'min:8|Password::defaults\\(\\)|Password::default\\(\\)' app`

Expected: no password-creation rule retains a standalone `min:8` complexity definition.

- [ ] **Step 2: Run the full automated suite**

Run: `php artisan test`

Expected: PASS with zero failures.

- [ ] **Step 3: Review the diff for scope and security**

Confirm only password validation, imports, the dedicated test, and this plan changed; confirm no password values are logged or exposed.
