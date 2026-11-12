# Auction document generation on production

Auction reports and the establishment/closing forms use the same `auction-reports`
queue and Chromium renderer. A worker for the default queue alone does not consume
these jobs. Local `composer dev` starts the dedicated worker automatically.

Run a persistent worker with the production process manager, using the site's PHP
CLI executable, application directory, and application service account:

```sh
/absolute/path/to/php /absolute/path/to/artisan queue:work auction_reports --queue=auction-reports --sleep=1 --tries=3 --timeout=420
```

The default `auction_reports` connection stores jobs centrally and has a 600-second
retry window. If `AUCTION_REPORT_QUEUE_CONNECTION` overrides it, run the worker on
that connection and retain a retry window longer than the jobs' 420-second timeout.
Do not point this connection at an individual tenant database.

The HTTP fallback also attempts to start a worker. Set `PHP_CLI_BINARY` to the
actual CLI executable when the server has multiple PHP installations. This value
is now included in `config/auction-renderer.php`, so it survives `config:cache`.
Automatic discovery uses Symfony's PHP executable finder rather than deriving a
CLI path from the PHP-FPM executable name. Child worker output is appended to
`storage/logs/auction-report-worker.log`; Laravel errors remain in the configured
application log. The web account needs permission to start child processes and
write that log for the fallback to work. A persistent worker avoids depending on
HTTP process spawning.

After changing deployment configuration, rebuild the configuration cache and
restart managed queue workers through the process manager. A worker keeps the
configuration/code loaded when it started.

## Identify the failing stage

Open the auction details page and inspect the document status response together
with the application and worker logs:

| Status/progress | Boundary to inspect |
| --- | --- |
| No generation row, or queuing error | Tenant migrations and the logged snapshot/SQL exception |
| `queued`, `0` | The details/status request has not dispatched this revision; inspect its HTTP response |
| `queued`, `5` | A job was dispatched; inspect the central queue, worker command/account and startup log |
| `processing`, `10`–`70` | The worker reached tenant generation; inspect the logged template/renderer exception |
| Failure near publication | Temporary directory and tenant private storage permissions, disk space and logged storage exception |

For renderer failures, check the executable/module paths as the worker account,
not only from an administrator's SSH shell:

```sh
/absolute/path/to/php -v
/absolute/path/to/node --version
/absolute/path/to/node -p "require.resolve('puppeteer')"
/absolute/path/to/chromium --version
```

Run the Node checks from the application directory. Puppeteer is already a runtime
dependency in `package.json`; the deployed installation must contain it and an
accessible Chrome/Chromium binary with its required system libraries. Configure
`BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_NPM_BINARY`,
`BROWSERSHOT_NODE_MODULE_PATH`, and `BROWSERSHOT_CHROME_PATH` only for the actual
server paths. The renderer also requires a writable PHP temporary directory and
private tenant storage.

The worker launcher regression is verified locally with an isolated FPM command
simulation. It does not establish which prerequisite failed on a live server.
Use the failing status/progress, exception and child-worker stderr to distinguish
worker startup from rendering, schema or storage failures before changing server
configuration.
