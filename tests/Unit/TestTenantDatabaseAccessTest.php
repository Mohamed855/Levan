<?php

declare(strict_types=1);

namespace Tests\Unit;

use Database\Seeders\admin\TestTenantSeeder;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;
use Tests\TestCase;

final class TestTenantDatabaseAccessTest extends TestCase
{
    private ConnectionResolverInterface $originalResolver;

    private $central;

    private $probe;

    protected function setUp(): void
    {
        parent::setUp();
        config(['seeding.test_seeders_enabled' => true, 'seeding.test_tenant_database' => 'hosting_test']);
        $this->central = \Mockery::mock(Connection::class);
        $this->probe = \Mockery::mock(Connection::class);
        $this->central->shouldReceive('getConfig')->once()->andReturn([
            'name' => 'mysql', 'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306,
            'database' => 'hosting_main', 'username' => 'hosting_user', 'password' => 'test-only',
            'url' => 'mysql://ignored-url', 'read' => ['database' => 'hosting_main'], 'write' => ['database' => 'hosting_main'],
        ]);
        DB::shouldReceive('connection')->with('mysql')->andReturn($this->central);
        DB::shouldReceive('build')->once()->with(\Mockery::on(fn (array $config): bool => $config['name'] === 'test_tenant_seed_probe' && $config['database'] === 'hosting_test'
            && $config['username'] === 'hosting_user'
            && array_intersect_key($config, array_flip(['url', 'read', 'write'])) === []
        ))->andReturn($this->probe);
        DB::shouldReceive('purge')->with('test_tenant_seed_probe')->once();
        DB::shouldReceive('purge')->with('mysql')->never();
        $this->originalResolver = Model::getConnectionResolver();
        Model::setConnectionResolver(DB::getFacadeRoot());
    }

    protected function tearDown(): void
    {
        Model::setConnectionResolver($this->originalResolver);
        parent::tearDown();
    }

    public function test_existing_accessible_database_does_not_require_create_database_permission(): void
    {
        $this->probe->shouldReceive('getPdo')->once()->andReturn(\Mockery::mock(PDO::class));
        $this->central->shouldReceive('statement')->never();
        $this->stopBeforeMetadataWrites();
        (new TestTenantSeeder)->run();
    }

    public function test_missing_database_is_created_then_rechecked_before_metadata_writes(): void
    {
        $this->probe->shouldReceive('getPdo')->once()->ordered()->andThrow($this->mysqlError(1049));
        $this->probe->shouldReceive('getPdo')->once()->ordered()->andReturn(\Mockery::mock(PDO::class));
        $this->central->shouldReceive('statement')->once()->with('CREATE DATABASE IF NOT EXISTS `hosting_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')->andReturn(true);
        $this->stopBeforeMetadataWrites();
        (new TestTenantSeeder)->run();
    }

    public function test_database_access_denial_stops_before_central_changes_and_does_not_attempt_create(): void
    {
        $this->probe->shouldReceive('getPdo')->once()->andThrow($this->mysqlError(1044));
        $this->central->shouldReceive('statement')->never();
        $this->central->shouldReceive('query')->never();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TEST_TENANT_DATABASE');
        (new TestTenantSeeder)->run();
    }

    public function test_hosting_create_denial_explains_precreation_and_grants_before_central_changes(): void
    {
        $this->probe->shouldReceive('getPdo')->once()->andThrow($this->mysqlError(1049));
        $this->central->shouldReceive('statement')->once()->andThrow($this->mysqlError(1044));
        $this->central->shouldReceive('query')->never();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hosting control panel');
        (new TestTenantSeeder)->run();
    }

    public function test_connection_failure_does_not_attempt_database_creation(): void
    {
        $this->probe->shouldReceive('getPdo')->once()->andThrow($this->mysqlError(2002));
        $this->central->shouldReceive('statement')->never();
        $this->central->shouldReceive('query')->never();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TEST_TENANT_DATABASE');
        (new TestTenantSeeder)->run();
    }

    private function stopBeforeMetadataWrites(): void
    {
        $this->central->shouldReceive('query')->once()->andThrow(new RuntimeException('Database preflight passed; stop before metadata writes.'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database preflight passed');
    }

    private function mysqlError(int $number): PDOException
    {
        $exception = new PDOException('Simulated MySQL error '.$number);
        $exception->errorInfo = ['HY000', $number, $exception->getMessage()];

        return $exception;
    }
}
