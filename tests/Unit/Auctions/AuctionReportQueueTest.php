<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class AuctionReportQueueTest extends TestCase
{
    public function test_reports_use_a_central_durable_queue_with_a_safe_retry_window(): void
    {
        $connection = config('auction-renderer.queue_connection');
        self::assertSame('auction_reports', $connection);
        self::assertSame('database', config("queue.connections.$connection.driver"));
        self::assertSame(config('tenancy.database.central_connection'), config("queue.connections.$connection.connection"));
        self::assertTrue(config("queue.connections.$connection.central"));
        self::assertGreaterThan((new GenerateAuctionReportsJob('test', 1, 1, 'revision'))->timeout, config("queue.connections.$connection.retry_after"));
    }

    public function test_development_startup_includes_the_report_worker(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        self::assertStringContainsString('queue:work auction_reports', implode(' ', $composer['scripts']['dev']));
    }

    public function test_php_cli_override_is_available_to_cached_configuration(): void
    {
        $previous = getenv('PHP_CLI_BINARY');
        putenv('PHP_CLI_BINARY=/opt/custom/php83/bin/php');

        try {
            $renderer = require config_path('auction-renderer.php');
            self::assertSame('/opt/custom/php83/bin/php', $renderer['php_binary'] ?? null);
        } finally {
            putenv($previous === false ? 'PHP_CLI_BINARY' : 'PHP_CLI_BINARY='.$previous);
        }
    }

    public function test_worker_uses_cached_cli_override_and_preserves_startup_errors_under_fpm(): void
    {
        $command = $this->workerCommand('/opt/custom/php83/bin/php');

        self::assertStringStartsWith(escapeshellarg('/opt/custom/php83/bin/php').' ', $command);
        self::assertStringContainsString('queue:work', $command);
        self::assertStringContainsString(escapeshellarg('auction_reports').' --queue='.escapeshellarg('auction-reports'), $command);
        self::assertStringContainsString('auction-report-worker.log', $command);
        self::assertStringEndsWith('2>&1 &', $command);
    }

    public function test_worker_finds_cli_without_reusing_the_fpm_executable(): void
    {
        $command = $this->workerCommand(null);

        self::assertStringStartsWith(escapeshellarg(PHP_BINARY).' ', $command);
        self::assertStringNotContainsString('php-fpm', $command);
    }

    private function workerCommand(?string $phpBinary): string
    {
        // Isolate the Linux/FPM simulation and intercept exec so no worker runs.
        $script = <<<'PHP'
<?php
namespace App\Services\Tenants\Auctions\Reports {
    const PHP_OS = 'Linux';
    const PHP_BINARY = '/usr/sbin/php-fpm8.3';
    function exec(string $command): void { echo $command; }
}
namespace {
    require getenv('AUCTION_WORKER_TEST_ROOT').'/vendor/autoload.php';
    $app = require getenv('AUCTION_WORKER_TEST_ROOT').'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    config(['cache.default' => 'array', 'auction-renderer.php_binary' => json_decode(getenv('AUCTION_WORKER_TEST_PHP'), true)]);
    putenv('PHP_CLI_BINARY');
    unset($_ENV['PHP_CLI_BINARY'], $_SERVER['PHP_CLI_BINARY']);
    app(\App\Services\Tenants\Auctions\Reports\AuctionReportService::class)->ensureQueueWorkerIsRunning();
}
PHP;
        $process = new Process([PHP_BINARY], base_path(), [
            'AUCTION_WORKER_TEST_ROOT' => base_path(),
            'AUCTION_WORKER_TEST_PHP' => json_encode($phpBinary, JSON_THROW_ON_ERROR),
        ], $script);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
