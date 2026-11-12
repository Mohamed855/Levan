# Shared Tenant Authentication Implementation Plan

**Goal:** Serve tenant authentication pages under `/admin/`, redirect tenant-specific entry links there, and always honor the explicitly requested tenant in hidden form inputs.

**Architecture:** Share the existing auth route definitions between canonical central-domain `/admin/` routes, tenant-path compatibility routes, and existing tenant-domain routes. Resolve explicit tenant context before session hints; clear prior tenant authentication state when deliberately changing tenant on guest auth routes. Keep authenticated tenant operations bound to their tenant and retain separate superadmin sessions.

**Scope:** Login, registration, password reset and OTP, email verification, password confirmation/update, logout, and two-factor authentication. Dashboard and business routes retain their tenant prefixes. Custom tenant domains retain their existing URLs.

- [x] Add failing browser-session regression tests for shared auth routes, switching tenants repeatedly, explicit hidden-input selection, malformed/unknown tenants, and isolation.
- [x] Extract auth routes and register canonical `/admin/` endpoints; redirect legacy GET links, preserving query parameters and valid signed verification links.
- [x] Update context resolution, route generation, and hidden fields in all auth forms; include tenant context in auth AJAX requests.
- [x] Update existing assertions for the requested canonical URLs and exercise login, OTP reset, two-factor, logout, and session isolation.
- [x] Review the diff for scope, complexity, and security; run relevant tests, PHP style checks, frontend build, and the broader suite where available.

**Verification:** `php artisan test tests/Feature/Auth/DatabasePanelSessionIsolationTest.php`, related auth suites, `php artisan test`, Pint on modified PHP files, and `npm run build`.

**Execution decision:** Work in the provided checkout with existing dependencies. The user's explicit request authorizes implementing these reversible changes; no commits, deployment, or changes to unrelated features.

**Review correction:** Welcome-email and front-page login links carry explicit `tenant_id` so fresh browsers and browsers with another tenant's context select the intended company. Navbar logout and two-factor links also retain their source tenant. The existing checkout callback keeps its explicit tenant-path entry, which redirects to the shared login after selecting the newly created tenant.

**Verified results:** 157 focused authentication/user/session tests passed with 1,280 assertions (`storage/logs/shared-tenant-auth-final-verification.log`). Frontend build passed. All modified PHP/Blade files passed PHP syntax checks; the changed tenant route collection compiled 624 unique routes. Independent review found no remaining introduced Critical/Important issues.

**Broader checks:** Full suite: 852 passed, 369 failed, 1 risky, 4 skipped (`storage/logs/shared-tenant-auth-full-suite.log`). Failures include missing seeded tenant `test`/plans/admin fixtures, required isolated auction-report database configuration, PDF/Poppler dependencies, and assertions in unrelated modules. No unrelated database seeding or test repairs were performed. Pint passed the new route/middleware and focused changed files; other touched files retain style failures also reproduced against their original HEAD versions. Full route caching is separately blocked by the existing duplicate `password.reset` name in unchanged admin/Fortify routes; tenant route compilation itself passed.
