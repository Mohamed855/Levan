<?php

declare(strict_types=1);

namespace Tests\Unit;

use Database\Seeders\admin\DatabaseSeeder;
use Database\Seeders\admin\TestTenantSeeder;
use Database\Seeders\tenant\AuctionsAndPropertiesSeeder;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class TestSeederOptInTest extends TestCase
{
    public function test_test_seeders_are_disabled_by_default_and_declared_in_environment_example(): void
    {
        // Verify the absent-variable default independently of the developer's .env opt-in.
        $script = 'require '.var_export(base_path('vendor/autoload.php'), true).'; echo json_encode(require '.var_export(config_path('seeding.php'), true).');';
        $process = new Process([PHP_BINARY, '-r', $script], base_path(), ['TEST_SEEDERS_ENABLED' => false]);
        $process->mustRun();
        self::assertSame(false, json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['test_seeders_enabled']);
        self::assertStringContainsString('TEST_SEEDERS_ENABLED=false', file_get_contents(base_path('.env.example')));
    }

    public function test_orchestrator_uses_explicit_opt_in_in_both_production_and_local(): void
    {
        foreach (['local', 'production'] as $environment) {
            $this->app->detectEnvironment(fn () => $environment);
            foreach ([false, true] as $enabled) {
                config(['seeding.test_seeders_enabled' => $enabled]);
                $seeder = new class extends DatabaseSeeder
                {
                    public array $invokedSeeders = [];

                    public function call($class, $silent = false, array $parameters = [])
                    {
                        $this->invokedSeeders = array_merge($this->invokedSeeders, (array) $class);

                        return $this;
                    }
                };
                $seeder->run();
                self::assertSame($enabled, in_array(TestTenantSeeder::class, $seeder->invokedSeeders, true), $environment);
            }
        }
    }

    public function test_direct_demo_entry_points_do_not_connect_to_a_database_without_opt_in(): void
    {
        config(['seeding.test_seeders_enabled' => false]);
        DB::shouldReceive('connection')->never();
        $resolver = Model::getConnectionResolver();
        Model::setConnectionResolver(DB::getFacadeRoot());
        try {
            foreach (['local', 'production'] as $environment) {
                $this->app->detectEnvironment(fn () => $environment);
                (new TestTenantSeeder)->run();
                (new AuctionsAndPropertiesSeeder)->run();
            }
        } finally {
            Model::setConnectionResolver($resolver);
        }
        self::assertTrue(true);
    }

    #[DataProvider('centralDatabaseNames')]
    public function test_test_database_cannot_match_the_central_database_resolved_from_db_url(string $testDatabase): void
    {
        config(['seeding.test_seeders_enabled' => true, 'seeding.test_tenant_database' => $testDatabase, 'database.connections.mysql.database' => 'raw_fallback']);
        $connection = \Mockery::mock(Connection::class);
        $connection->shouldReceive('getConfig')->once()->andReturn(['database' => 'actual_central']);
        DB::shouldReceive('connection')->with('mysql')->once()->andReturn($connection);
        $resolver = Model::getConnectionResolver();
        Model::setConnectionResolver(DB::getFacadeRoot());
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('TEST_TENANT_DATABASE must name a separate, valid test database.');
            (new TestTenantSeeder)->run();
        } finally {
            Model::setConnectionResolver($resolver);
        }
    }

    public static function centralDatabaseNames(): array
    {
        return [['actual_central'], ['ACTUAL_CENTRAL']];
    }
}
