# Auction Template Designer and Sample Library Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-page image backgrounds, pointer resizing, a bundled Arabic font library, a permissioned central sample CRUD, and identical snapshot-based preview/PDF/PNG rendering for auction templates.

**Architecture:** Extend the existing JSON schema to a backward-compatible version 2 and keep each published version self-contained by copying assets into version-scoped storage. Reuse one designer and one Blade rendering document for samples, templates, tenant preview, and Browsershot exports; preserve the current file-template output as a legacy fallback only when no snapshot exists.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, vanilla ES modules and Pointer Events, Vite 5, PHPUnit 11, Spatie Permission 8, Yajra DataTables 12, `spatie/browsershot:^5.4`, Node 22 LTS+, `puppeteer:^24.0`, Chrome/Chromium.

**Spec:** `docs/superpowers/specs/2026-10-25-auction-template-designer-samples-design.md`

## Global Constraints

-   Samples are central and never tenant-specific.
-   Applying a sample replaces the whole design after confirmation and creates no live link.
-   Sample permissions are independent and the seeded initial SuperAdmin receives them.
-   Sample thumbnails are optional and fall back to `public/static/brand/logos/logo.svg`.
-   Only PNG, JPEG, and WebP design images are accepted; remote URLs and SVG are rejected.
-   All fonts are locally bundled, freely licensed for commercial embedding, and accompanied by license files.
-   Preview, snapshot PDF, and snapshot PNG must share one rendering document.
-   Keep all existing output route names and retain legacy output for auctions without snapshots.
-   Do not mass-rewrite existing schemas or snapshots; normalize them at runtime.
-   Do not modify unrelated files in the currently dirty worktree.

## Review Focus

-   A tampered schema references `../`, `file://`, UNC, or HTTP assets: Task 3 must reject it before reading or copying a file.
-   A sample becomes inactive after its shelf was loaded: Task 7 must reject application/save without losing the current client state.
-   A template version reuses an earlier background and is then edited: Task 8 must prove that the older version's bytes and path remain unchanged.
-   Browser export loads mixed central and tenant assets: Tasks 9 and 10 must prove both are converted to local data URIs and no remote request is needed.
-   A legacy schema uses `canvas.background`, `page_number`, nested `position`, camelCase styles, or bound `type: text`: Task 2 must apply the authoritative mapping and Task 9 must render it without a database rewrite.

---

### Task 1: Rendering runtime configuration and local font registry

**Files:**

-   Modify: `composer.json`
-   Modify: `composer.lock`
-   Modify: `package.json`
-   Modify: `package-lock.json`
-   Modify: `.env.example`
-   Create: `config/auction-renderer.php`
-   Create: `config/auction-template-fonts.php`
-   Create: `app/Support/Auctions/AuctionFontRegistry.php`
-   Create: `resources/assets/fonts/auction-designer/<family>/*.woff2`
-   Create: `resources/assets/fonts/auction-designer/licenses/<family>-OFL.txt`
-   Test: `tests/Unit/Auctions/AuctionFontRegistryTest.php`

**Interfaces:**

-   Consumes: no feature-specific interface.
-   Produces: `AuctionFontRegistry::all(): array`, `AuctionFontRegistry::find(string $slug): array`, `AuctionFontRegistry::supportsWeight(string $slug, int $weight): bool`, and `AuctionFontRegistry::cssFor(array $slugs): string`.

-   [ ] **Step 1: Write the failing registry tests**

    Add tests asserting all 14 approved slugs exist, `almarai` is the default, each family has at least weight 400, an unknown slug returns no definition, unsupported weights return false, generated CSS contains only requested families, every WOFF2 path exists, and every family has a license file.

-   [ ] **Step 2: Run the registry test and confirm RED**

    Run: `php artisan test tests/Unit/Auctions/AuctionFontRegistryTest.php`

    Expected: FAIL because the registry/config/assets do not exist.

-   [ ] **Step 3: Add the rendering dependencies and configuration**

    Require `spatie/browsershot:^5.4` and `puppeteer:^24.0`. Define `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_NPM_BINARY`, `BROWSERSHOT_CHROME_PATH`, and `BROWSERSHOT_TIMEOUT` in `.env.example`; map them in `config/auction-renderer.php` without hardcoded machine paths.

-   [ ] **Step 4: Add the approved fonts and registry**

    Bundle Cairo, Tajawal, Almarai, Alexandria, IBM Plex Sans Arabic, Noto Sans Arabic, Noto Kufi Arabic, Noto Naskh Arabic, Amiri, Reem Kufi, Changa, Markazi Text, Mada, and Aref Ruqaa as WOFF2. Record family label, CSS family, supported weights, asset paths, and license path in `config/auction-template-fonts.php`; implement the four registry methods with strict lookup and deterministic CSS.

-   [ ] **Step 5: Run focused verification**

    Run: `php artisan test tests/Unit/Auctions/AuctionFontRegistryTest.php`

    Expected: PASS.

    Run: `npm run build`

    Expected: exit 0 with font assets emitted by Vite.

-   [ ] **Step 6: Commit**

    ```bash
    git add composer.json composer.lock package.json package-lock.json .env.example config/auction-renderer.php config/auction-template-fonts.php app/Support/Auctions/AuctionFontRegistry.php resources/assets/fonts/auction-designer tests/Unit/Auctions/AuctionFontRegistryTest.php
    git commit -m "feat: add auction renderer runtime and Arabic fonts"
    ```

### Task 2: Version-2 schema normalization and validation

**Files:**

-   Modify: `app/Support/Auctions/TemplateSchema.php`
-   Modify: `app/Rules/TemplateSchemaRule.php`
-   Modify: `tests/Feature/AuctionTemplatesFeatureTest.php`
-   Create: `tests/Unit/Auctions/TemplateSchemaV2Test.php`

**Interfaces:**

-   Consumes: `AuctionFontRegistry::find()` and `supportsWeight()` from Task 1.
-   Produces: `TemplateSchema::normalize(string|array $schema, string $templateType): array`, the existing `validate()` returning normalized version-2 data, and the existing `slots()` supporting normalized schemas.

-   [ ] **Step 1: Write failing normalization tests**

    Cover no version, `canvas.background`, `page_number` without ID, nested `position`, camelCase styles, unknown and known legacy font-family names, `default_value`, bound and unbound `type: text`, a version-2 page background, default `almarai`, default `cover/center`, default line height, and default image aspect lock. Assert IDs are deterministic, bound text becomes `variable`, unbound text stays `text`, fallback content is retained, normalization is idempotent, the caller's array is not mutated, and brochure pages remain intact.

-   [ ] **Step 2: Write failing validation tests**

    Cover valid local background paths and all three fits; reject unknown fonts, unsupported weights, remote URLs, `file://`, UNC paths, `..`, SVG paths, invalid line height, elements smaller than 20 x 20, and elements whose rectangle exceeds the canvas.

-   [ ] **Step 3: Run focused tests and confirm RED**

    Run: `php artisan test tests/Unit/Auctions/TemplateSchemaV2Test.php tests/Feature/AuctionTemplatesFeatureTest.php --filter=template_schema`

    Expected: FAIL on missing version-2 normalization/rules.

-   [ ] **Step 4: Implement the schema contract**

    Add `SCHEMA_VERSION = 2`, background fit constants, default style values, and `normalize()`. Implement every mapping in the spec's “Authoritative legacy mapping” section without geometry clamping. Extend `validate()` to normalize before Laravel validation, query the font registry in the after-hook, enforce approved path prefixes (`auction_template_samples/` and `auction_templates/`), and validate element rectangles against canvas bounds for newly submitted designs.

-   [ ] **Step 5: Run focused tests**

    Run: `php artisan test tests/Unit/Auctions/TemplateSchemaV2Test.php tests/Feature/AuctionTemplatesFeatureTest.php --filter=template_schema`

    Expected: PASS.

-   [ ] **Step 6: Commit**

    ```bash
    git add app/Support/Auctions/TemplateSchema.php app/Rules/TemplateSchemaRule.php tests/Unit/Auctions/TemplateSchemaV2Test.php tests/Feature/AuctionTemplatesFeatureTest.php
    git commit -m "feat: define auction template schema v2"
    ```

### Task 3: Secure immutable design-asset service

**Files:**

-   Create: `app/Exceptions/Auctions/InvalidDesignAssetException.php`
-   Create: `app/Services/Admin/AuctionTemplates/AuctionDesignAssetService.php`
-   Test: `tests/Unit/Auctions/AuctionDesignAssetServiceTest.php`

**Interfaces:**

-   Consumes: normalized version-2 schema from Task 2 and the `central_public` disk.
-   Produces: `finalizeSchema(array $schema, array $uploads, string $targetPrefix, array $allowedSourcePrefixes): array`, `publicUrl(string $path): string`, `dataUri(string $path): string`, and `deleteCreatedFiles(array $paths): void`.

-   [ ] **Step 1: Write failing storage and cloning tests**

    On a fake `central_public` disk, assert uploaded page files are stored under the target prefix with UUID names, inherited sample/template files are copied into the target prefix, schemas are rewritten to copied paths, and separate target prefixes never share a path.

-   [ ] **Step 2: Write failing security and rollback tests**

    Assert remote, UNC, traversal, disallowed-prefix, missing, SVG, oversized, and over-dimension images raise `InvalidDesignAssetException`; assert `deleteCreatedFiles()` removes only paths returned by this service.

-   [ ] **Step 3: Run the service tests and confirm RED**

    Run: `php artisan test tests/Unit/Auctions/AuctionDesignAssetServiceTest.php`

    Expected: FAIL because the service does not exist.

-   [ ] **Step 4: Implement the asset service**

    Use `Storage::disk('central_public')`, server-detected MIME, `getimagesize()`, 10 MiB and 10,000-pixel limits, prefix allowlists, and streamed copies. Return a normalized schema with relative paths only; collect created paths so callers can compensate after transaction failure.

-   [ ] **Step 5: Run focused tests**

    Run: `php artisan test tests/Unit/Auctions/AuctionDesignAssetServiceTest.php`

    Expected: PASS.

-   [ ] **Step 6: Commit**

    ```bash
    git add app/Exceptions/Auctions/InvalidDesignAssetException.php app/Services/Admin/AuctionTemplates/AuctionDesignAssetService.php tests/Unit/Auctions/AuctionDesignAssetServiceTest.php
    git commit -m "feat: add immutable auction design assets"
    ```

### Task 4: Shared designer core, page backgrounds, fonts, and pointer resizing

**Files:**

-   Modify: `resources/views/admin/auction-templates/partials/form.blade.php`
-   Modify: `resources/views/admin/auction-templates/create.blade.php`
-   Modify: `resources/views/admin/auction-templates/edit.blade.php`
-   Modify: `resources/assets/js/admin/auction-templates/designer.js`
-   Modify: `resources/assets/scss/admin/pages/auction-template-create.scss`
-   Create: `resources/views/admin/auction-designs/partials/designer.blade.php`
-   Create: `resources/assets/js/admin/auction-templates/designer/state.js`
-   Create: `resources/assets/js/admin/auction-templates/designer/geometry.js`
-   Create: `resources/assets/js/admin/auction-templates/designer/backgrounds.js`
-   Create: `resources/assets/js/admin/auction-templates/designer/fonts.js`
-   Create: `resources/assets/js/admin/auction-templates/designer/tests/state.test.js`
-   Create: `resources/assets/js/admin/auction-templates/designer/tests/geometry.test.js`
-   Modify: `package.json`

**Interfaces:**

-   Consumes: schema version 2 and font definitions serialized from `AuctionFontRegistry::all()`.
-   Produces: reusable Blade designer partial; `replaceSchema(nextSchema)`, `serializeSchema()`, `resizeElement(element, handle, delta, canvas, lockAspect)`, and page background file inputs named `page_backgrounds[<page-id>]`.

-   [ ] **Step 1: Add failing Node tests for state replacement and geometry**

    Use Node 22's built-in `node:test`. Assert replacement deep-clones the incoming schema; resize supports all eight handles, display-to-schema scale, 20 x 20 minimum, canvas clamping, and optional aspect locking.

-   [ ] **Step 2: Run the JavaScript tests and confirm RED**

    Add `test:auction-designer` to `package.json`, then run: `npm run test:auction-designer`

    Expected: FAIL because the modules do not exist.

-   [ ] **Step 3: Extract the reusable designer partial**

    Move only designer markup into `admin.auction-designs.partials.designer`, parameterized by `schema`, `type`, `mode`, `fontOptions`, `showSampleShelf`, and `assetBaseUrl`. Keep template metadata and submit actions in the existing template form.

-   [ ] **Step 4: Implement backgrounds and font controls**

    Add upload/replace/remove, color, fit, and position controls per selected page. Preview new uploads with `URL.createObjectURL()` and revoke replaced URLs. Add font, supported weight, size, line height, color, and alignment controls; load a font stylesheet only when selected.

-   [ ] **Step 5: Implement pointer resizing**

    Render eight focusable handles for the selected element, use pointer capture, convert deltas by canvas scale, update the DOM without rebuilding it during movement, then persist state on pointer end. Keep numeric fields synchronized.

-   [ ] **Step 6: Run focused verification**

    Run: `npm run test:auction-designer`

    Expected: PASS.

    Run: `npm run build`

    Expected: exit 0.

-   [ ] **Step 7: Commit**

    ```bash
    git add resources/views/admin/auction-templates resources/views/admin/auction-designs resources/assets/js/admin/auction-templates resources/assets/scss/admin/pages/auction-template-create.scss package.json package-lock.json
    git commit -m "feat: enhance auction template designer controls"
    ```

### Task 5: Sample persistence, policy, and permissions

**Files:**

-   Create: `database/migrations/admin/2026_10_25_000001_create_auction_template_samples_table.php`
-   Create: `app/Models/Admin/AuctionTemplates/AuctionTemplateSample.php`
-   Create: `app/Policies/Admin/AuctionTemplates/AuctionTemplateSamplePolicy.php`
-   Create: `database/factories/AuctionTemplateSampleFactory.php`
-   Create: `database/seeders/admin/AuctionTemplateSamplesPermissionsSeeder.php`
-   Modify: `database/seeders/admin/PermissionsRolesSeeder.php`
-   Modify: `database/seeders/admin/DatabaseSeeder.php`
-   Modify: `database/seeders/admin/AdminUserSeeder.php`
-   Modify: `tests/Unit/SuperAdminContractTest.php`
-   Create: `tests/Feature/Admin/AuctionTemplateSamplePersistenceTest.php`
-   Create: `tests/Feature/Admin/AuctionTemplateSamplePermissionsTest.php`

**Interfaces:**

-   Consumes: Spatie Permission admin guard.
-   Produces: central `AuctionTemplateSample` model with `scopeActive()` and `scopeByType()`, plus the four approved permissions.

-   [ ] **Step 1: Write failing migration/model tests**

    Assert central connection, casts for `schema` and `is_active`, soft deletion, type scope, active scope, creator relation, and newest-first library ordering.

-   [ ] **Step 2: Write failing permission/seeder tests**

    Assert all four permissions exist under section `auction_template_samples_management`, the SuperAdmin role owns them after repeatable seeding, and the initial admin user owns them after `AdminUserSeeder`.

-   [ ] **Step 3: Run focused tests and confirm RED**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSamplePersistenceTest.php tests/Feature/Admin/AuctionTemplateSamplePermissionsTest.php tests/Unit/SuperAdminContractTest.php --filter=auction_template_samples`

    Expected: FAIL because table/model/permissions are absent.

-   [ ] **Step 4: Implement persistence and policy**

    Create fields `name`, `type`, `description`, `preview_image_path`, `schema`, `is_active`, `created_by`, timestamps, and soft deletes. Limit policy methods to the four independent permission names; toggle status maps to update/edit.

-   [ ] **Step 5: Implement idempotent seeders**

    Add the permission section to `PermissionsRolesSeeder`, create the focused backfill seeder, call it after `PermissionsRolesSeeder` and before `AdminUserSeeder`, and retain `AdminUserSeeder`'s all-admin-permissions synchronization.

-   [ ] **Step 6: Run focused tests**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSamplePersistenceTest.php tests/Feature/Admin/AuctionTemplateSamplePermissionsTest.php tests/Unit/SuperAdminContractTest.php --filter=auction_template_samples`

    Expected: PASS.

-   [ ] **Step 7: Commit**

    ```bash
    git add database/migrations/admin/2026_10_25_000001_create_auction_template_samples_table.php app/Models/Admin/AuctionTemplates/AuctionTemplateSample.php app/Policies/Admin/AuctionTemplates/AuctionTemplateSamplePolicy.php database/factories/AuctionTemplateSampleFactory.php database/seeders/admin tests/Feature/Admin/AuctionTemplateSamplePersistenceTest.php tests/Feature/Admin/AuctionTemplateSamplePermissionsTest.php tests/Unit/SuperAdminContractTest.php
    git commit -m "feat: add auction template sample permissions and model"
    ```

### Task 6: Complete sample CRUD and shared-designer forms

**Files:**

-   Create: `app/Http/Requests/Admin/AuctionTemplateSamples/StoreAuctionTemplateSampleRequest.php`
-   Create: `app/Http/Requests/Admin/AuctionTemplateSamples/UpdateAuctionTemplateSampleRequest.php`
-   Create: `app/Http/Requests/Admin/AuctionTemplateSamples/UpdateAuctionTemplateSampleStatusRequest.php`
-   Create: `app/Actions/Admin/AuctionTemplateSamples/CreateAuctionTemplateSampleAction.php`
-   Create: `app/Actions/Admin/AuctionTemplateSamples/UpdateAuctionTemplateSampleAction.php`
-   Create: `app/Actions/Admin/AuctionTemplateSamples/DeleteAuctionTemplateSampleAction.php`
-   Create: `app/Http/Controllers/Admin/AuctionTemplateSamples/AuctionTemplateSampleController.php`
-   Create: `app/DataTables/Admin/AuctionTemplateSamples/AuctionTemplateSampleDataTable.php`
-   Create: `resources/views/admin/auction-template-samples/index.blade.php`
-   Create: `resources/views/admin/auction-template-samples/create.blade.php`
-   Create: `resources/views/admin/auction-template-samples/edit.blade.php`
-   Create: `resources/views/admin/auction-template-samples/show.blade.php`
-   Create: `resources/views/admin/auction-template-samples/partials/form.blade.php`
-   Create: `resources/views/admin/auction-template-samples/action.blade.php`
-   Create: `resources/assets/js/admin/auction-template-samples/index.js`
-   Modify: `routes/admin.php`
-   Modify: `resources/views/admin/layouts/sections/menu/verticalMenu.blade.php`
-   Modify: `tests/Unit/AdminSidebarCoverageTest.php`
-   Create: `tests/Feature/Admin/AuctionTemplateSamplesCrudTest.php`

**Interfaces:**

-   Consumes: shared designer from Task 4, sample model/policy from Task 5, `TemplateSchema::validate()`, and `AuctionDesignAssetService::finalizeSchema()`.
-   Produces: route namespace `admin.auction-template-samples.*` and complete CRUD/status UI.

-   [ ] **Step 1: Write failing CRUD feature tests**

    Cover permission denial per action, index filters, create with and without thumbnail, show, update design, activate/deactivate, soft delete, invalid image MIME/size/dimensions, unique name within type, and fallback logo markup.

-   [ ] **Step 2: Run CRUD tests and confirm RED**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSamplesCrudTest.php tests/Unit/AdminSidebarCoverageTest.php`

    Expected: FAIL because routes and handlers do not exist.

-   [ ] **Step 3: Implement FormRequests and transactional actions**

    Authorize using the independent permissions. Validate schema through `TemplateSchemaRule`, thumbnail and `page_backgrounds.*` by MIME/size/dimensions, and type against `AuctionTemplate::TYPES`. Actions store assets, update the model in a transaction, and delete newly created files when the transaction fails.

-   [ ] **Step 4: Implement controller, DataTable, routes, and menu**

    Keep the controller limited to requests/actions/views. Register `library` routes later before resource wildcard routes. Add filters for type and active state and expose a menu item only inside `@can('auction_template_samples.view')`.

-   [ ] **Step 5: Implement the CRUD views**

    Reuse the shared designer with `mode=sample` and `showSampleShelf=false`. Use the existing image-upload component for the optional thumbnail and platform-logo fallback. Implement default, loading, success, validation, and disabled-submit states.

-   [ ] **Step 6: Run focused verification**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSamplesCrudTest.php tests/Unit/AdminSidebarCoverageTest.php`

    Expected: PASS.

    Run: `npm run build`

    Expected: exit 0.

-   [ ] **Step 7: Commit**

    ```bash
    git add app/Http/Requests/Admin/AuctionTemplateSamples app/Actions/Admin/AuctionTemplateSamples app/Http/Controllers/Admin/AuctionTemplateSamples app/DataTables/Admin/AuctionTemplateSamples resources/views/admin/auction-template-samples resources/assets/js/admin/auction-template-samples routes/admin.php resources/views/admin/layouts/sections/menu/verticalMenu.blade.php tests/Feature/Admin/AuctionTemplateSamplesCrudTest.php tests/Unit/AdminSidebarCoverageTest.php
    git commit -m "feat: add auction template sample management"
    ```

### Task 7: Authorized sample shelf and full design replacement

**Files:**

-   Create: `app/Http/Resources/Admin/AuctionTemplateSamples/AuctionTemplateSampleCardResource.php`
-   Create: `app/Http/Resources/Admin/AuctionTemplateSamples/AuctionTemplateSampleDesignResource.php`
-   Modify: `app/Http/Controllers/Admin/AuctionTemplateSamples/AuctionTemplateSampleController.php`
-   Modify: `routes/admin.php`
-   Modify: `resources/views/admin/auction-designs/partials/designer.blade.php`
-   Create: `resources/assets/js/admin/auction-templates/designer/samples.js`
-   Modify: `resources/assets/js/admin/auction-templates/designer.js`
-   Modify: `resources/assets/scss/admin/pages/auction-template-create.scss`
-   Modify: `resources/assets/js/admin/auction-templates/designer/tests/state.test.js`
-   Create: `tests/Feature/Admin/AuctionTemplateSampleLibraryTest.php`

**Interfaces:**

-   Consumes: `replaceSchema()` from Task 4 and active/type scopes from Task 5.
-   Produces: `GET admin/auction-template-samples/library?type=...` returning cards shaped as `{id,name,type,thumbnail_url}`, and `GET admin/auction-template-samples/{sample}/design?type=...` returning `{id,type,schema,asset_urls}` for a selected active schema. Successful selection also sets transient form field `source_sample_id`.

-   [ ] **Step 1: Write failing endpoint tests**

    Assert `view` permission is required, cards include thumbnail fallback URL but not schema, only active requested-type samples are returned newest first, design returns normalized schema and central asset URLs, and inactive/wrong-type/deleted samples return 404.

-   [ ] **Step 2: Extend the state test for replacement**

    Assert confirmed selection replaces every page and canvas property, cancel preserves the exact prior schema, and the loaded object is deep-cloned so subsequent edits do not mutate the response fixture.

-   [ ] **Step 3: Run focused tests and confirm RED**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSampleLibraryTest.php`

    Run: `npm run test:auction-designer`

    Expected: at least one FAIL in each suite.

-   [ ] **Step 4: Implement resources and endpoints**

    Return stable JSON contracts, not Eloquent models. Place `library` before resource wildcard routes. Recheck active/type status on the design endpoint and escape all user-provided labels through JSON/DOM text APIs.

-   [ ] **Step 5: Implement shelf states and replacement interaction**

    Render cards above the toolbar with loading skeleton, empty message, retryable error, hover/focus/active states, and horizontal overflow. On click, request the design, ask for confirmation only when dirty, call `replaceSchema()`, set `source_sample_id`, refresh page tabs/background previews/fonts, and preserve the current design and prior source ID on every error/cancel path.

-   [ ] **Step 6: Run focused verification**

    Run: `php artisan test tests/Feature/Admin/AuctionTemplateSampleLibraryTest.php`

    Run: `npm run test:auction-designer && npm run build`

    Expected: all commands exit 0.

-   [ ] **Step 7: Commit**

    ```bash
    git add app/Http/Resources/Admin/AuctionTemplateSamples app/Http/Controllers/Admin/AuctionTemplateSamples/AuctionTemplateSampleController.php routes/admin.php resources/views/admin/auction-designs/partials/designer.blade.php resources/assets/js/admin/auction-templates resources/assets/scss/admin/pages/auction-template-create.scss tests/Feature/Admin/AuctionTemplateSampleLibraryTest.php
    git commit -m "feat: add sample library to auction designer"
    ```

### Task 8: Immutable template-version assets and snapshot compatibility

**Files:**

-   Modify: `app/Http/Requests/Admin/AuctionTemplates/StoreAuctionTemplateRequest.php`
-   Modify: `app/Http/Requests/Admin/AuctionTemplates/UpdateAuctionTemplateRequest.php`
-   Modify: `app/Actions/Admin/AuctionTemplates/CreateAuctionTemplateAction.php`
-   Modify: `app/Actions/Admin/AuctionTemplates/UpdateAuctionTemplateAction.php`
-   Modify: `app/Http/Controllers/Admin/AuctionTemplates/AuctionTemplateController.php`
-   Modify: `resources/views/admin/auction-templates/create.blade.php`
-   Modify: `resources/views/admin/auction-templates/edit.blade.php`
-   Modify: `app/Actions/Tenants/Auctions/CreateAuctionAction.php`
-   Modify: `app/Actions/Tenants/Auctions/UpdateAuctionAction.php`
-   Modify: `tests/Feature/AuctionTemplatesFeatureTest.php`

**Interfaces:**

-   Consumes: `AuctionDesignAssetService::finalizeSchema()` and schema version 2.
-   Produces: every new `AuctionTemplateVersion::schema` references only its own version directory; tenant snapshots retain those immutable references.

-   [ ] **Step 1: Write failing action tests**

    Assert create stores v1 backgrounds under `auction_templates/{id}/versions/1`, update stores the next version under its own directory, an authorized active same-type `source_sample_id` permits copying sample bytes, inactive/wrong-type/unauthorized source samples are rejected, replacing an upload affects only the new version, transaction failure cleans newly stored assets, and forms use `multipart/form-data`.

-   [ ] **Step 2: Write the snapshot regression test**

    Create/publish v1, create a tenant auction snapshot, create v2 with a changed background, then assert the auction snapshot schema and v1 file bytes/path remain unchanged.

-   [ ] **Step 3: Run focused tests and confirm RED**

    Run: `php artisan test tests/Feature/AuctionTemplatesFeatureTest.php --filter='background|immutable|snapshot'`

    Expected: FAIL because version assets are not finalized.

-   [ ] **Step 4: Update requests, forms, and actions**

    Validate `page_backgrounds.*` and transient `source_sample_id`; re-query the sample as active/same-type and require `auction_template_samples.view` before allowing its prefix; normalize and validate schema before persistence; calculate the version number before choosing the target prefix; finalize assets within the action workflow; and compensate stored files after any thrown transaction error. Do not persist `source_sample_id` or client preview URLs.

-   [ ] **Step 5: Preserve tenant snapshot behavior**

    Keep copying the complete published schema into `template_snapshot`. Normalize before snapshot creation so new auctions always store schema version 2 while existing snapshots remain untouched.

-   [ ] **Step 6: Run focused tests**

    Run: `php artisan test tests/Feature/AuctionTemplatesFeatureTest.php --filter='background|immutable|snapshot'`

    Expected: PASS.

-   [ ] **Step 7: Commit**

    ```bash
    git add app/Http/Requests/Admin/AuctionTemplates app/Actions/Admin/AuctionTemplates app/Http/Controllers/Admin/AuctionTemplates/AuctionTemplateController.php resources/views/admin/auction-templates app/Actions/Tenants/Auctions tests/Feature/AuctionTemplatesFeatureTest.php
    git commit -m "feat: persist immutable auction template assets"
    ```

### Task 9: One snapshot rendering document for admin and tenant previews

**Files:**

-   Create: `app/Exceptions/Auctions/MissingAuctionDesignAssetException.php`
-   Create: `app/ViewModels/Auctions/AuctionDesignViewModel.php`
-   Create: `app/Services/Tenants/Auctions/AuctionDesignAssetResolver.php`
-   Modify: `app/Services/Tenants/Auctions/AuctionTemplateRenderer.php`
-   Create: `resources/views/components/shared/auctions/design-document.blade.php`
-   Modify: `resources/views/tenant/auctions/preview.blade.php`
-   Modify: `app/Http/Controllers/Tenants/Auctions/AuctionsController.php`
-   Create: `tests/Unit/Auctions/AuctionDesignAssetResolverTest.php`
-   Create: `tests/Feature/Tenant/AuctionDesignPreviewTest.php`

**Interfaces:**

-   Consumes: snapshot schema, `AuctionFontRegistry::cssFor()`, central backgrounds, and tenant cover images.
-   Produces: `AuctionTemplateRenderer::render(Auction $auction, string $type): AuctionDesignViewModel`, `AuctionDesignAssetResolver::centralDataUri(string $path): string`, and `tenantDataUri(string $path): string`.

-   [ ] **Step 1: Write failing asset resolver tests**

    Assert central and tenant files resolve from their correct disks, MIME-correct data URIs are returned, missing/traversal/remote paths throw `MissingAuctionDesignAssetException`, and no resolver method performs HTTP requests.

-   [ ] **Step 2: Write failing preview tests**

    Assert a page renders its color, background data URI, fit mode, exact box geometry, resolved font family/weight/line-height, bound auction name, and tenant cover image. Assert an empty optional binding renders canonical fallback `content`, a required empty binding still produces a validation error, and each authoritative legacy mapping renders correctly.

-   [ ] **Step 3: Run focused tests and confirm RED**

    Run: `php artisan test tests/Unit/Auctions/AuctionDesignAssetResolverTest.php tests/Feature/Tenant/AuctionDesignPreviewTest.php`

    Expected: FAIL because the shared view model/document do not exist.

-   [ ] **Step 4: Implement the view model and resolvers**

    Make the view model expose normalized schema, resolved page backgrounds, resolved binding values, used font slugs, canvas dimensions, and document type. Keep disk selection explicit and reject all non-relative paths.

-   [ ] **Step 5: Implement the shared Blade document**

    Use escaped Blade output, absolute geometry, RTL defaults, background fit mapping, and CSS generated only for used fonts. Make admin/tenant preview wrap this document rather than duplicating element-rendering logic.

-   [ ] **Step 6: Run focused verification**

    Run: `php artisan test tests/Unit/Auctions/AuctionDesignAssetResolverTest.php tests/Feature/Tenant/AuctionDesignPreviewTest.php`

    Expected: PASS.

-   [ ] **Step 7: Commit**

    ```bash
    git add app/Exceptions/Auctions/MissingAuctionDesignAssetException.php app/ViewModels/Auctions/AuctionDesignViewModel.php app/Services/Tenants/Auctions resources/views/components/shared/auctions/design-document.blade.php resources/views/tenant/auctions/preview.blade.php app/Http/Controllers/Tenants/Auctions/AuctionsController.php tests/Unit/Auctions/AuctionDesignAssetResolverTest.php tests/Feature/Tenant/AuctionDesignPreviewTest.php
    git commit -m "feat: unify auction design preview rendering"
    ```

### Task 10: Browsershot PDF/PNG exporter and legacy fallback

**Files:**

-   Create: `app/Contracts/Auctions/BrowserDocumentRenderer.php`
-   Create: `app/Services/Auctions/BrowsershotDocumentRenderer.php`
-   Create: `app/Services/Auctions/AuctionSnapshotExporter.php`
-   Create: `app/Exceptions/Auctions/AuctionExportException.php`
-   Modify: `app/Providers/AppServiceProvider.php`
-   Modify: `app/Services/Tenants/Auctions/AuctionOutputService.php`
-   Modify: `app/Http/Controllers/Tenants/Auctions/AuctionOutputController.php`
-   Modify: `resources/views/tenant/auctions/show.blade.php`
-   Modify: `app/DataTables/Tenants/Auctions/AuctionsDataTable.php`
-   Create: `tests/Fakes/FakeBrowserDocumentRenderer.php`
-   Create: `tests/Unit/Auctions/AuctionSnapshotExporterTest.php`
-   Modify: `tests/Feature/AuctionTemplatesFeatureTest.php`
-   Create: `tests/Feature/Renderer/AuctionBrowserExportTest.php`

**Interfaces:**

-   Consumes: `AuctionTemplateRenderer::render()` and the shared Blade document from Task 9.
-   Produces: `BrowserDocumentRenderer::pdf(string $html, string $path, int $width, int $height): void`, `png(string $html, string $path, int $width, int $height): void`, `AuctionSnapshotExporter::exportPdf(Auction $auction, string $type): string`, and `exportPng(Auction $auction, string $type): string`.

-   [ ] **Step 1: Write failing exporter unit tests with a fake browser**

    Assert snapshot outputs select the new exporter, brochure sends all pages to PDF, billboard/social PDF use exact canvas size, social PNG uses exact pixel dimensions, generated files are returned, and renderer failure removes temporary files and raises `AuctionExportException`.

-   [ ] **Step 2: Write failing compatibility tests**

    Assert auctions with snapshots never query active legacy template IDs; auctions without snapshots continue through the existing file-based service; show-page download buttons appear for snapshot outputs even when legacy IDs are null; route names remain unchanged.

-   [ ] **Step 3: Run unit/feature tests and confirm RED**

    Run: `php artisan test tests/Unit/Auctions/AuctionSnapshotExporterTest.php tests/Feature/AuctionTemplatesFeatureTest.php --filter='export|output|download|legacy'`

    Expected: FAIL because exporter interfaces and routing logic are absent.

-   [ ] **Step 4: Implement and bind the browser renderer**

    Configure node/npm/chrome paths and timeout from `config/auction-renderer.php`; disable arbitrary network access; wait for `document.fonts.ready`; print backgrounds; use CSS page size for PDF; use selector clipping and device scale 1 for PNG; wrap process errors without exposing commands or paths to users.

-   [ ] **Step 5: Integrate snapshot-first output**

    Make `AuctionOutputService` delegate to `AuctionSnapshotExporter` whenever the requested snapshot exists and otherwise execute the current legacy implementation unchanged. Update controller error handling and show/DataTable download gating to recognize snapshots.

-   [ ] **Step 6: Add the real-browser integration test**

    In group `renderer`, export a fixture containing an Arabic font, background, bound text, and cover image. Assert non-empty PDF, expected PDF page count for brochure, 1080 x 1080 social PNG, and no failed network request. Skip with an explicit reason only when `BROWSERSHOT_CHROME_PATH` is absent.

-   [ ] **Step 7: Run verification**

    Run: `php artisan test tests/Unit/Auctions/AuctionSnapshotExporterTest.php tests/Feature/AuctionTemplatesFeatureTest.php --filter='export|output|download|legacy'`

    Expected: PASS.

    Run in configured renderer environment: `php artisan test tests/Feature/Renderer/AuctionBrowserExportTest.php --group=renderer`

    Expected: PASS with Chromium configured; never silently skip in CI/deployment validation.

-   [ ] **Step 8: Commit**

    ```bash
    git add app/Contracts/Auctions app/Services/Auctions app/Exceptions/Auctions/AuctionExportException.php app/Providers/AppServiceProvider.php app/Services/Tenants/Auctions/AuctionOutputService.php app/Http/Controllers/Tenants/Auctions/AuctionOutputController.php tests/Fakes/FakeBrowserDocumentRenderer.php tests/Unit/Auctions/AuctionSnapshotExporterTest.php tests/Feature/AuctionTemplatesFeatureTest.php tests/Feature/Renderer/AuctionBrowserExportTest.php resources/views/tenant/auctions/show.blade.php app/DataTables/Tenants/Auctions/AuctionsDataTable.php
    git commit -m "feat: export auction snapshots through Chromium"
    ```

### Task 11: End-to-end acceptance, operational documentation, and release gate

**Files:**

-   Create: `docs/operations/auction-template-renderer.md`
-   Modify: `docs/baselines/manual-smoke-checklist.md`
-   Modify: `README.md`
-   Test: all files introduced or modified by Tasks 1-10

**Interfaces:**

-   Consumes: the complete feature.
-   Produces: deployable operational requirements and a verified acceptance checklist.

-   [ ] **Step 1: Document runtime provisioning**

    Record Node 22+, Puppeteer 23+, Chrome/Chromium installation, environment variables, storage permissions, queue/web user access, renderer health check, failure logs, and rollback behavior. Include Windows development and Linux production examples without committing machine-specific paths.

-   [ ] **Step 2: Add the manual acceptance matrix**

    Cover all three types; sample with/without thumbnail; active/inactive sample; cancel/confirm replacement; multi-page backgrounds; all fit modes; resize handles and aspect lock; every bundled font; preview/PDF/PNG parity; missing required binding; and legacy auction output.

-   [ ] **Step 3: Run backend focused suites**

    Run: `php artisan test tests/Unit/Auctions tests/Feature/Admin/AuctionTemplateSamplePermissionsTest.php tests/Feature/Admin/AuctionTemplateSamplesCrudTest.php tests/Feature/Admin/AuctionTemplateSampleLibraryTest.php tests/Feature/Tenant/AuctionDesignPreviewTest.php tests/Feature/AuctionTemplatesFeatureTest.php`

    Expected: 0 failures and 0 errors.

-   [ ] **Step 4: Run frontend and build verification**

    Run: `npm run test:auction-designer`

    Expected: 0 failures.

    Run: `npm run build`

    Expected: exit 0.

-   [ ] **Step 5: Run formatting and full regression tests**

    Run: `vendor/bin/pint --test`

    Expected: exit 0.

    Run: `php artisan test`

    Expected: 0 failures and 0 errors. Existing unrelated baseline failures must be documented with their pre-feature reproduction; do not label the feature complete while a feature-owned failure remains.

-   [ ] **Step 6: Run real renderer gate**

    Run: `php artisan test tests/Feature/Renderer/AuctionBrowserExportTest.php --group=renderer`

    Expected: PASS on the same Chrome/Node image intended for production.

-   [ ] **Step 7: Audit the original requirements**

    Confirm, point by point: per-page backgrounds; mouse resize; sample CRUD; cards above toolbar; complete replace and continued editing; one library per type; optional thumbnail/fallback logo; central SuperAdmin ownership; independent permissions and initial-admin seeding; active/inactive only; local Arabic fonts; and identical final PDF/PNG output.

-   [ ] **Step 8: Commit**

    ```bash
    git add docs/operations/auction-template-renderer.md docs/baselines/manual-smoke-checklist.md README.md
    git commit -m "docs: add auction renderer operations and acceptance guide"
    ```
