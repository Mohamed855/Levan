<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

final class TestDatabaseGuardTest extends TestCase
{
    #[DataProvider('safeDatabases')]
    public function test_it_allows_only_explicit_test_databases(string $driver, string $name): void
    {
        TestDatabaseGuard::assertSafe('testing', $this->database($driver, $name), $this->tenancy());
        $this->addToAssertionCount(1);
    }

    public static function safeDatabases(): array
    {
        return [['sqlite', ':memory:'], ['mysql', 'levan_test_central'], ['mysql', 'levan_test_central_1'], ['mariadb', 'levan_test_central_worker2']];
    }

    #[DataProvider('unsafeDatabases')]
    public function test_it_refuses_non_test_databases(string $driver, string $name): void
    {
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('testing', $this->database($driver, $name), $this->tenancy());
    }

    public static function unsafeDatabases(): array
    {
        return [['mysql', 'etmam_levan'], ['mysql', ''], ['mysql', 'levan_test_central_backup_prod'], ['sqlite', 'database/database.sqlite'], ['pgsql', 'production']];
    }

    public function test_it_refuses_a_non_testing_environment(): void
    {
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('production', $this->database('sqlite', ':memory:'), $this->tenancy());
    }

    public function test_it_refuses_a_real_tenant_prefix(): void
    {
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('testing', $this->database('sqlite', ':memory:'), ['prefix' => 'tenant', 'central_connection' => 'test']);
    }

    public function test_it_refuses_urls_that_override_the_safe_database_name(): void
    {
        $database = $this->database('mysql', 'levan_test_central');
        $database['connections']['test']['url'] = 'mysql://localhost/etmam_levan';
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('testing', $database, $this->tenancy());
    }

    public function test_it_checks_the_central_connection_separately(): void
    {
        $database = $this->database('sqlite', ':memory:');
        $database['connections']['central'] = ['driver' => 'mysql', 'database' => 'etmam_levan'];
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('testing', $database, ['central_connection' => 'central', 'prefix' => 'levan_test_tenant_']);
    }

    public function test_it_checks_the_tenant_template_connection_separately(): void
    {
        $database = $this->database('sqlite', ':memory:');
        $database['connections']['template'] = ['driver' => 'mysql', 'database' => 'etmam_levan'];
        $this->expectException(RuntimeException::class);
        TestDatabaseGuard::assertSafe('testing', $database, $this->tenancy() + ['template_tenant_connection' => 'template']);
    }

    private function database(string $driver, string $name): array
    {
        return ['default' => 'test', 'connections' => ['test' => ['driver' => $driver, 'database' => $name]]];
    }

    private function tenancy(): array
    {
        return ['central_connection' => 'test', 'prefix' => 'levan_test_tenant_'];
    }
}
