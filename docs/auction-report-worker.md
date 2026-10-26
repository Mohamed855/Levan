# Automatic auction report generation

Auction saves enqueue PDF generation after commit. The `auction_reports` connection stores jobs in the central `jobs` table on the `auction-reports` queue. The job initializes its explicit tenant before reading the snapshot or writing private files.

Use `composer dev` to start the web server, frontend, ordinary queue worker and report worker together. When starting the web server separately, keep `composer reports:work` running as well. Running `php artisan serve` alone does not execute queued jobs.

Production must supervise this long-running process, with automatic restart enabled:

```sh
php artisan queue:work auction_reports --sleep=1 --tries=3 --timeout=420
```

Restart the worker after deployments with `php artisan queue:restart`. Its retry window is 600 seconds, longer than the 420-second job timeout, to avoid simultaneous attempts rendering the same revision. See [Laravel queue workers and timeouts](https://laravel.com/docs/12.x/queues#job-expiration-and-timeouts).

For a deployment upgrading from the old default queue, allow its existing report jobs to finish before removing its worker. New report jobs use the dedicated connection. A database fresh reset must not run while a report job for that tenant is processing.
