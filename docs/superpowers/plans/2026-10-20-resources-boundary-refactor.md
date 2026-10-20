# Resources Boundary Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `resources/` predictable by keeping all assets under `resources/assets/` and all Blade views under `resources/views/`, consistently separated into Admin, Tenant, Web, and Shared boundaries.

**Architecture:** Assets are organized type-first, then by boundary, e.g. `assets/js/tenant` and `assets/scss/admin`. Blade views are organized boundary-first: `views/admin`, `views/tenant`, and `views/web`. Laravel-discovered shared components, mail views, and unified error views remain in their conventional top-level view locations.

**Tech Stack:** Laravel Blade, Vite, SCSS, JavaScript.

**Spec:** User-approved resources structure in this conversation (2026-10-20).

## Global Constraints

- Do not change page behavior, route names, authorization, or rendered content while relocating files.
- Use `tenant` consistently; remove the plural `tenants` naming from views and assets.
- Use `web` consistently; remove the old `site` view and asset boundary.
- Every changed JavaScript, CSS, or SCSS reference requires `npm run build`.
- Verify every moved Blade reference (`view`, `@extends`, `@include`, components, mailables, and error rendering) before deleting a source directory.
- Delete an old file or directory only when repository-wide references to it are zero.

## Target Structure

```text
resources/
├── assets/
│   ├── js/{admin,tenant,web,shared}/
│   ├── scss/{admin,tenant,web,shared}/
│   ├── css/{admin,tenant,web,shared}/
│   ├── menu/{admin,tenant,web}/
│   └── vendor/
└── views/
    ├── admin/{auth,layouts,partials,...}/
    ├── tenant/{auth,layouts,partials,...}/
    ├── web/{layouts,partials,...}/
    ├── components/{admin,tenant,web,shared,...}/
    ├── emails/{admin,tenant,web,shared}/
    └── errors/
```

## Review Focus

- Vite must discover every relocated entry and build successfully.
- A Blade view must not retain an old `site.` or `tenants.` reference after its path changes.
- Laravel anonymous-component names must match their final `components/` paths.
- Tenant and Admin layouts must continue loading their correct boundary assets.
- `resources/menu` consumers must be updated before menu JSON files move.

---

### Task 1: Create an inventory and a refactor contract test

**Files:**
- Create: `tests/Unit/ResourcesBoundaryContractTest.php`
- Inspect: `vite.config.js`, all `resources/` files, PHP view consumers, mailables, exception rendering, and menu consumers

- [ ] Write a failing contract test asserting that the final boundary directories exist and obsolete root asset directories do not.
- [ ] Write source-reference assertions that reject `resources/views/site`, `resources/views/tenants`, `resources/js`, `resources/scss`, `resources/css`, and `resources/menu` after the migration.
- [ ] Run the test and confirm the expected failure before moving files.
- [ ] Produce a file-to-file migration map for every asset/view that has a runtime consumer.

### Task 2: Normalize shared framework resources

**Files:**
- Move: root legacy assets into `resources/assets/{js,scss,css}/shared`
- Move: `resources/menu/*.json` into `resources/assets/menu/{admin,tenant,web}` according to each consumer
- Modify: Vite config and menu-loading code/configuration

- [ ] Move only shared JavaScript, SCSS, CSS, and menu files to their target locations.
- [ ] Update imports and resource-path consumers without changing asset content.
- [ ] Run the contract test and `npm run build`.

### Task 3: Normalize Web assets and views

**Files:**
- Move: `resources/views/web/**` to `resources/views/web/**`
- Move: `resources/assets/{js,scss,css}/site/**` to their `web` equivalents
- Modify: Site controllers, Blade `@extends`/`@include`, Vite entry references, and Web components

- [ ] Update the contract test for a Web view and asset reference.
- [ ] Move the files and update `site.*` view references to `web.*`.
- [ ] Move web-specific anonymous components from `components/site` to `components/web` and update their Blade tags.
- [ ] Run focused site feature tests and `npm run build`.

### Task 4: Normalize Tenant assets and views

**Files:**
- Move: `resources/views/tenant/**` to `resources/views/tenant/**`
- Move: both `resources/assets/{js,scss,css}/tenants/**` and legacy tenant assets to `resources/assets/{js,scss,css}/tenant/**`
- Modify: Tenant controllers, tenant layout includes, Blade view references, and Vite entry references

- [ ] Update the contract test for representative Tenant layouts, authentication, a CRUD administration, and an archive view.
- [ ] Consolidate the duplicate `tenant`/`tenants` asset boundaries under singular `tenant`.
- [ ] Standardize each active administration folder to its existing `index`, `create`, `edit`, `show`, `action`, and `partials` files without altering page behavior.
- [ ] Run focused tenant tests and `npm run build`.

### Task 5: Normalize Admin assets and views

**Files:**
- Move: legacy Admin assets to `resources/assets/{js,scss,css}/admin/**`
- Retain/normalize: `resources/views/admin/**`
- Modify: Admin layouts, controllers, Blade references, and Vite entry references

- [ ] Update the contract test for Admin authentication, dashboard, a CRUD administration, and archive views.
- [ ] Apply the same administration file convention without renaming public routes or changing rendered output.
- [ ] Run focused Admin tests and `npm run build`.

### Task 6: Consolidate shared views and delete verified leftovers

**Files:**
- Retain/organize: `resources/views/components/**`, `resources/views/emails/**`, `resources/views/errors/**`
- Delete: obsolete legacy source directories only after zero-reference checks
- Modify: mailables, error rendering, component references, baseline documentation, and the contract test

- [ ] Classify shared components and move boundary-specific components to `components/{admin,tenant,web}` only where their component names are updated together.
- [ ] Consolidate tenant mail templates under `views/emails/tenant` and update all mailables.
- [ ] Confirm unified error pages remain under `views/errors` and have no legacy Admin/Tenant/Site error duplicates.
- [ ] Remove old root assets and plural/legacy view directories after `rg` reports no references.
- [ ] Run `php artisan view:cache`, `php artisan view:clear`, focused tests, `npm run build`, and the resources contract test.

### Task 7: Final verification

**Files:**
- Verify: all modified resource references and `vite.config.js`

- [ ] Run `git diff --check`.
- [ ] Run `rg` for obsolete paths and confirm zero results.
- [ ] Run `php artisan route:cache` then `php artisan route:clear`.
- [ ] Run the project test command; report any pre-existing failures separately.
- [ ] Confirm the final tree matches the Target Structure exactly.
