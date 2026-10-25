# Auction Template Designer and Sample Library Design

**Date:** 2026-10-25

**Status:** Approved in conversation

## Intent

Turn the auction-template designer into the single source of truth for template authoring, tenant preview, and final PDF/PNG output. Super Admins must also be able to maintain a central library of editable starter samples for brochure, billboard, and social templates.

## Goals

- Allow an independent image background on every design page while retaining a fallback background color.
- Support `cover`, `contain`, and `stretch` background fitting.
- Let admins resize text, variable, and image boxes by dragging visible handles.
- Bundle a curated, locally hosted library of popular freely licensed Arabic fonts.
- Add a complete central CRUD for auction-template samples, including independent permissions.
- Show active samples as thumbnail cards above the designer toolbar, filtered by the current template type.
- Replace the complete current design after confirmation when a sample is selected.
- Keep the applied sample fully editable and independent from future sample changes.
- Make tenant preview and generated PDF/PNG use the same rendering semantics.
- Preserve existing auctions and legacy file-based templates during migration.

## Non-goals

- Tenant-specific sample libraries.
- Runtime font uploads or a font-management CRUD.
- Live linking between an applied sample and the resulting template.
- Arbitrary external image URLs or SVG uploads.
- Removing legacy `*_template_id` output support before old auctions have a safe fallback.
- Adding new output formats beyond the current brochure PDF, billboard PDF, social PDF, and social PNG routes.

## Users and authorization

- Samples are stored in the central database.
- Super Admins manage the sample library.
- Other central admins can access sample operations only when granted the corresponding permission.
- Independent permissions are:
  - `auction_template_samples.view`
  - `auction_template_samples.create`
  - `auction_template_samples.edit`
  - `auction_template_samples.delete`
- Sample activation/deactivation uses `auction_template_samples.edit`.
- The initial admin created by `AdminUserSeeder` receives all four permissions through the existing SuperAdmin/all-admin-permissions synchronization.
- Viewing the sample shelf in the template designer requires `auction_template_samples.view`; editing a template still requires the existing auction-template permission.

## Sample lifecycle

Each sample has:

- name;
- type: `brochure`, `billboard`, or `social`;
- optional description;
- optional preview image;
- complete version-2 design schema;
- `is_active` flag;
- creator and timestamps;
- soft deletion.

There is no ordering field. Active samples are shown newest first within the selected type. Inactive samples remain visible in the management index but never appear in the designer shelf.

The preview image is optional. When absent, the card renders `public/static/brand/logos/logo.svg` as the fallback. The preview image is descriptive only and is not used as a design background.

Selecting a sample performs a full replacement:

1. If the current design is dirty, show a confirmation dialog.
2. Cancel preserves the current design without changes.
3. Confirm replaces canvas, pages, backgrounds, elements, and styles.
4. The loaded design is immediately editable.
5. Selection writes the sample ID to a transient `source_sample_id` form field; it is never persisted as a live relationship.
6. Saving revalidates that the source sample is active, matches the template type, and is visible to the current admin.
7. Saving a template copies all referenced sample assets into the new immutable template-version directory.
8. Editing, disabling, or deleting the source sample later cannot change the saved template.

## Designer layout and behavior

The designer is shared by template and sample create/edit forms.

Order inside the designer:

1. Sample shelf, only in template mode and only for authorized users.
2. Page tabs.
3. Element toolbar.
4. Canvas.
5. Selected-element properties and selected-page background properties.

The sample shelf supports loading, empty, error, default, hover, focus-visible, and active states. It is horizontally scrollable on narrow screens. Cards show the preview/fallback image, sample name, and type label.

### Per-page backgrounds

Each page owns:

```json
{
  "background": {
    "color": "#ffffff",
    "image": null,
    "fit": "cover",
    "position": "center"
  }
}
```

- `image` is a central-disk relative path after persistence.
- New local files are previewed with an object URL but are submitted separately as `page_backgrounds[<page-uuid>]`.
- Allowed formats are PNG, JPEG, and WebP.
- SVG and external URLs are rejected.
- Image limits are 10 MiB and 10,000 x 10,000 pixels.
- `cover` maps to CSS `background-size: cover`.
- `contain` maps to CSS `background-size: contain`.
- `stretch` maps to CSS `background-size: 100% 100%`.
- All modes use `no-repeat`; initial position is `center`.
- Removing a background sets `image` to null while retaining the page color.

### Mouse resizing

Selecting an element exposes eight handles: north, south, east, west, and four corners.

- Side handles change one dimension.
- Corner handles change both dimensions.
- All pointer movement is converted from displayed-canvas coordinates into schema-canvas coordinates.
- Minimum element size is 20 x 20 schema pixels.
- Elements are clamped to the canvas bounds.
- Image elements default to locked aspect ratio for corner resizing; the properties panel exposes a lock toggle.
- Text and variable boxes default to free resizing.
- Numeric `x`, `y`, `width`, and `height` fields remain synchronized during pointer interaction.
- Pointer Events support mouse and touch input.

## Font library

Fonts are local project assets, never fetched from a third-party service at runtime. The initial curated list is:

- Cairo
- Tajawal
- Almarai
- Alexandria
- IBM Plex Sans Arabic
- Noto Sans Arabic
- Noto Kufi Arabic
- Noto Naskh Arabic
- Amiri
- Reem Kufi
- Changa
- Markazi Text
- Mada
- Aref Ruqaa

Only files with licenses that allow bundling and commercial use may be added. Every family includes its license text in the repository. Web rendering uses WOFF2; server export uses the same WOFF2 data embedded in generated HTML. Fonts are lazy-loaded in the designer and only fonts used by a schema are embedded in an export.

Text and variable element styles support:

- `font_family` using the registry slug;
- `font_size`;
- `font_weight`, limited to weights actually bundled for the family;
- `line_height` from 0.8 to 3.0;
- `color`;
- `text_align`;
- existing background color and border radius.

Existing schemas without `font_family` normalize to `almarai`.

### Authoritative legacy mapping

Normalization supports both the current flat designer schema and the older fixtures already present in the project:

- missing `schema_version` becomes version 2 in memory;
- `canvas.background` becomes each page's background color only when that page has no explicit background color;
- a page with `page_number` but no `id` receives deterministic ID `page-{page_number}`; otherwise its one-based array index is used;
- `position.x/y/width/height` is flattened into the canonical element geometry fields;
- style keys `fontSize`, `fontFamily`, `fontWeight`, `lineHeight`, `textAlign`, `backgroundColor`, and `borderRadius` map to their snake_case equivalents;
- font family names are matched case-insensitively against the registry; an unknown legacy family falls back to `almarai`;
- an old `type: text` element with a non-empty `binding` becomes `type: variable`;
- `default_value` becomes canonical `content` and acts as fallback display content only when a non-required binding is empty;
- an old `type: text` element without a binding remains static text and takes `content` from `content`, then `default_value`, then an empty string;
- required bindings still fail when empty and never use fallback content.

Normalization is deterministic and idempotent: normalizing an already normalized schema returns the same data. It does not rewrite database rows or silently clamp legacy geometry.

## Schema version 2

Canonical shape:

```json
{
  "schema_version": 2,
  "canvas": {"width": 1080, "height": 1080},
  "pages": [
    {
      "id": "page-uuid",
      "background": {
        "color": "#ffffff",
        "image": null,
        "fit": "cover",
        "position": "center"
      },
      "elements": [
        {
          "id": "element-uuid",
          "type": "variable",
          "binding": "auction.name",
          "x": 50,
          "y": 50,
          "width": 400,
          "height": 70,
          "required": false,
          "lock_aspect_ratio": false,
          "style": {
            "font_family": "cairo",
            "font_size": 26,
            "font_weight": 700,
            "line_height": 1.4,
            "color": "#173f35",
            "background_color": "#ffffff",
            "text_align": "right",
            "border_radius": 0
          }
        }
      ]
    }
  ]
}
```

`TemplateSchema::normalize()` upgrades missing/version-1 fields at runtime. Existing database rows and tenant snapshots are not mass-rewritten. Validation rejects unknown font slugs, unsupported weights, remote image URLs, traversal segments, and image paths outside approved central storage prefixes.

## Immutable asset storage

Paths are version scoped:

- sample backgrounds: `auction_template_samples/{sample-id}/backgrounds/{uuid}.{ext}`;
- sample thumbnails: `auction_template_samples/{sample-id}/preview/{uuid}.{ext}`;
- template backgrounds: `auction_templates/{template-id}/versions/{version}/backgrounds/{uuid}.{ext}`.

Creating a template version copies every persisted background into that version directory, including backgrounds inherited from another template version or a sample. Files are never overwritten in place. Therefore a published version and every tenant snapshot remain visually immutable.

Sample update replaces only the sample's current assets. Sample deletion is soft deletion; force cleanup is outside this feature. Failed database transactions remove files created during the failed operation.

## Rendering and export architecture

The schema snapshot is canonical. A shared Blade document renders:

- admin preview;
- tenant preview;
- PDF export;
- PNG export.

Static design backgrounds come from `central_public`; dynamic auction images such as `auction.cover_image` come from `tenant_public`. The renderer resolves both explicitly and converts assets to data URIs before invoking the browser renderer. Generated documents cannot make arbitrary network requests.

The exporter uses `spatie/browsershot` with Puppeteer and a project-controlled Chrome/Chromium binary. Production requirements are PHP 8.2+, Node 22 LTS+, Puppeteer 23+, and Chrome/Chromium. Configuration exposes binary paths and timeout values through environment variables.

Current output routes remain stable:

- brochure: multi-page PDF;
- billboard: PDF;
- social: PDF;
- social image: PNG.

When `template_snapshot[type]` exists, output always uses the unified renderer. Old auctions without a snapshot retain the current file-template implementation as a compatibility fallback.

## Error handling

- Missing required binding: validation error identifying the binding.
- Missing optional binding with `content`: render the stored fallback content.
- Missing stored design asset: domain exception identifying the template/version and asset.
- Browser runtime unavailable: logged operational error and user-safe export failure message.
- Sample disabled between shelf load and selection/save: selection/save is rejected and the current design remains intact.
- Invalid or unauthorized sample: 404/403 without leaking schema or asset paths.
- Failed export: temporary output and browser working files are deleted.

## Acceptance criteria

- Every page can upload, replace, remove, and independently fit a background image.
- Every element can be resized with pointer handles and remains within canvas bounds.
- The selected local font renders consistently in designer, preview, PDF, and PNG.
- Sample management is a complete authorized central CRUD with active/inactive state.
- Independent sample permissions are assigned to the seeded initial SuperAdmin.
- The designer shows active samples of the current type only.
- Missing sample thumbnails use the platform logo.
- Applying a sample fully replaces the design after confirmation and leaves it editable.
- Saved templates are independent from subsequent sample changes.
- Published version assets and tenant snapshots remain immutable.
- Current download URLs continue to work.
- Legacy auctions without snapshots continue using the legacy output path.
