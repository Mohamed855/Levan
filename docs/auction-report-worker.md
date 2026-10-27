# Automatic auction report generation

Auction saves enqueue PDF generation after commit. The `auction_reports` connection stores jobs in the central `jobs` table on the `auction-reports` queue. The job initializes its explicit tenant before reading the snapshot or writing private files.

Use `composer dev` to start the web server, frontend, ordinary queue worker and report worker together. When starting the web server separately, keep `composer reports:work` running as well. Running `php artisan serve` alone does not execute queued jobs.

Production must supervise this long-running process, with automatic restart enabled:

```sh
php artisan queue:work auction_reports --sleep=1 --tries=3 --timeout=420
```

Restart the worker after deployments with `php artisan queue:restart`. Its retry window is 600 seconds, longer than the 420-second job timeout, to avoid simultaneous attempts rendering the same revision. See [Laravel queue workers and timeouts](https://laravel.com/docs/12.x/queues#job-expiration-and-timeouts).

For a deployment upgrading from the old default queue, allow its existing report jobs to finish before removing its worker. New report jobs use the dedicated connection. A database fresh reset must not run while a report job for that tenant is processing.

PDF evidence additionally requires Poppler's `pdftoppm` on the worker PATH, or its absolute executable path in `AUCTION_REPORT_PDF_RASTERIZER`. Conversion runs with argument arrays, a private temporary directory and a 90-second timeout. All document pages enter normal evidence pagination. Chromium waits for navigation/image loading and `document.fonts.ready`; it does not decode duplicate 600-DPI SVG backgrounds, which exceed Chromium's decoded-image memory budget.

For a repeatable reference comparison, install Pillow, NumPy and pypdf in an isolated tooling runtime and provide that Python executable explicitly:

```powershell
./scripts/auction-reports/render-reference-comparison.ps1 -ReferenceDirectory $env:AUCTION_REPORT_REFERENCE_DIR -PythonBinary '<isolated-python>'
```

The command generates both golden PDFs, measures every page, rasterizes references and output with the same executable/DPI, exports masks, checks page size/fonts and writes contact sheets, overlays, heatmaps and `comparison-summary.json`. A failed page returns a nonzero exit code. Review masks alongside diffs; a static-only comparison does not validate business-data binding or image content.

For a generated live PDF, run `assert-pdf-contract.ps1` with `-Pdf`, `-Type outputs|report`, `-Pages` and `-PythonBinary`. Continuations can legitimately increase the live count beyond 7/21. Production code never reads the external reference directory.

When rebuilding assets, run `package-assets.php` with the reference directory and full GE/Nizar files, then `measure-numeric-fonts.php`, then package again to record the numeric-metrics hash. This compiler is a development tool, not a request/worker dependency. The generated assets are deployed with the application.

When regenerating the measured image frames, first render the 21-page golden fixture HTML, run `measure-image-frames.php`, and package once more. Its local clip-space rectangles let missing-image logos and PDF pages use the actual visible frame. The original photograph transforms/crops remain intact for ordinary photos. Frame metadata is deployed and hash-checked with the artwork; no browser geometry measurement runs in production pagination.

Acceptance tooling also supports `verify-queued-reports.php --auto-save --default-queue`: it saves the two restored acceptance auctions through `UpdateAuctionAction` without changing their business values and records the automatically created generations. A running ordinary `auction_reports` worker must consume them; `--collect` checks readiness/private storage and downloads through the authorized controller. The feature suite separately verifies automatic dispatch after both create and update, HTTP download permissions and private-disk tenant isolation.
