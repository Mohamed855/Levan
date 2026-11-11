<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RetiredDomainDataCleanupTest extends TestCase
{
    private string $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantDatabase = tempnam(sys_get_temp_dir(), 'levan_domain_cleanup_test_');
        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.domain_cleanup_fixture' => ['driver' => 'sqlite', 'database' => $this->tenantDatabase, 'prefix' => ''],
        ]);
        DB::purge('mysql');
        DB::purge('domain_cleanup_fixture');
        $this->fixture(DB::connection('mysql'));
        $this->fixture(DB::connection('domain_cleanup_fixture'));
        DB::connection('mysql')->table('tenants')->insert(['id' => 'test-tenant', 'db_database' => $this->tenantDatabase, 'access_type' => 'domain']);
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        DB::purge('domain_cleanup_fixture');
        if (isset($this->tenantDatabase) && is_file($this->tenantDatabase)) {
            unlink($this->tenantDatabase);
        }
        parent::tearDown();
    }

    public function test_cleanup_removes_domain_data_from_central_and_tenant_databases_without_touching_other_records(): void
    {
        (require database_path('migrations/admin/2026_11_11_000001_remove_retired_domain_data.php'))->up();

        foreach (['mysql', 'domain_cleanup_fixture'] as $name) {
            $db = DB::connection($name);
            $schema = $db->getSchemaBuilder();
            foreach (['control_domains', 'domain_payment_attempts', 'cpanel_accounts', 'transfer_domains'] as $table) {
                $this->assertFalse($schema->hasTable($table));
            }
            $this->assertSame(0, $db->table('domains')->count());
            $this->assertFalse($schema->hasColumn('domains', 'transfer_code'));
            $this->assertFalse($schema->hasColumn('plans', 'domain_management_enabled'));
            $this->assertFalse($schema->hasColumn('tenants', 'custom_domain_active'));
            $this->assertSame(['new_subscription'], $db->table('bills')->pluck('type')->all());
            $this->assertSame(['dashboard.view'], $db->table('permissions')->pluck('name')->all());
            $this->assertSame([2], $db->table('role_has_permissions')->pluck('permission_id')->all());
            $this->assertSame([2], $db->table('model_has_permissions')->pluck('permission_id')->all());
            $this->assertSame(['unrelated-job', 'Unrelated\\DomainException'], $db->table('jobs')->pluck('payload')->all());
            $this->assertSame(1, $db->table('activity_log')->count());
            $this->assertSame(['name' => 'keep'], json_decode($db->table('activity_log')->value('properties'), true));
            $this->assertSame(['name' => 'keep', 'snapshot' => ['licenses' => 3]], json_decode($db->table('bills')->value('metadata'), true));
            $this->assertSame(['feature-a'], json_decode($db->table('plans')->value('features'), true));
            $this->assertSame(['keep', 'keep-domain-word'], $db->table('telescope_entries')->orderBy('sequence')->pluck('uuid')->all());
            $this->assertSame(['keep', 'keep-domain-word'], $db->table('telescope_entries_tags')->pluck('entry_uuid')->all());
            $this->assertSame(['unrelated-domainException'], $db->table('cache')->pluck('key')->all());
        }
        $this->assertSame('path', DB::connection('mysql')->table('tenants')->value('access_type'));
    }

    public function test_cleanup_can_run_twice_without_recreating_or_deleting_unrelated_data(): void
    {
        $migration = require database_path('migrations/admin/2026_11_11_000001_remove_retired_domain_data.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, DB::connection('mysql')->table('bills')->count());
        $this->assertSame(1, DB::connection('domain_cleanup_fixture')->table('bills')->count());
        $this->assertSame(1, DB::connection('mysql')->table('tenants')->count());
    }

    public function test_unavailable_tenant_database_stops_cleanup_before_any_deletion(): void
    {
        DB::connection('mysql')->table('tenants')->insert(['id' => 'missing-tenant', 'db_database' => $this->tenantDatabase.'.missing']);

        try {
            (require database_path('migrations/admin/2026_11_11_000001_remove_retired_domain_data.php'))->up();
            $this->fail('All tenant connections must be checked before cleanup.');
        } catch (SQLiteDatabaseDoesNotExistException) {
            $this->assertSame(2, DB::connection('mysql')->table('bills')->count());
            $this->assertTrue(DB::connection('mysql')->getSchemaBuilder()->hasTable('control_domains'));
        }
    }

    private function fixture(Connection $db): void
    {
        $schema = $db->getSchemaBuilder();
        $schema->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('db_database')->nullable();
            $table->string('access_type')->default('path');
            $table->boolean('custom_domain_active')->default(true);
            $table->timestamp('domain_activated_at')->nullable();
        });
        foreach (['control_domains', 'domain_payment_attempts', 'cpanel_accounts', 'transfer_domains'] as $name) {
            $schema->create($name, fn (Blueprint $table) => $table->id());
            $db->table($name)->insert(['id' => 1]);
        }
        $schema->create('domains', function (Blueprint $table): void {
            $table->id();
            $table->string('domain');
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('transfer_code')->nullable();
            $table->string('trasnfer_status')->nullable();
        });
        $db->table('domains')->insert(['domain' => 'example.test']);
        $schema->create('plans', function (Blueprint $table): void {
            $table->id();
            $table->boolean('domain_management_enabled')->default(true);
            $table->json('features')->nullable();
        });
        $db->table('plans')->insert(['features' => json_encode(['feature-a', 'إدارة النطاقات', 'domain.connect'])]);
        $schema->create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->json('metadata')->nullable();
        });
        $db->table('bills')->insert([
            ['type' => 'new_domain', 'metadata' => null],
            ['type' => 'new_subscription', 'metadata' => json_encode(['name' => 'keep', 'domain' => 'example.test', 'snapshot' => ['domain_management_enabled' => true, 'licenses' => 3]])],
        ]);
        $schema->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $db->table('permissions')->insert([['id' => 1, 'name' => 'domain.view'], ['id' => 2, 'name' => 'dashboard.view']]);
        foreach (['role_has_permissions', 'model_has_permissions'] as $name) {
            $schema->create($name, fn (Blueprint $table) => $table->unsignedBigInteger('permission_id'));
            $db->table($name)->insert([['permission_id' => 1], ['permission_id' => 2]]);
        }
        $schema->create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->text('payload');
        });
        $db->table('jobs')->insert([['payload' => 'App\\Jobs\\Domain\\CreateDomainProceesJob'], ['payload' => 'unrelated-job'], ['payload' => 'Unrelated\\DomainException']]);
        $schema->create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->json('properties')->nullable();
        });
        $db->table('activity_log')->insert([
            ['subject_type' => 'App\\Models\\Admin\\ControlDomain\\ControlDomain', 'properties' => null],
            ['subject_type' => 'App\\Models\\Admin\\Plans\\Plan', 'properties' => json_encode(['name' => 'keep', 'domain_management_enabled' => true])],
        ]);
        $schema->create('telescope_entries', function (Blueprint $table): void {
            $table->bigIncrements('sequence');
            $table->string('uuid')->unique();
            $table->text('content');
        });
        $schema->create('telescope_entries_tags', function (Blueprint $table): void {
            $table->string('entry_uuid');
            $table->string('tag');
        });
        $entries = [];
        $tags = [];
        foreach (range(1, 501) as $index) {
            $entries[] = ['uuid' => 'retired-'.$index, 'content' => '{"domain_management_enabled":true}'];
            $tags[] = ['entry_uuid' => 'retired-'.$index, 'tag' => 'retired'];
        }
        $entries[] = ['uuid' => 'keep', 'content' => '{"name":"keep"}'];
        $entries[] = ['uuid' => 'retired-url', 'content' => json_encode(['uri' => 'https://platform.example/test/domains/setting'])];
        $entries[] = ['uuid' => 'keep-domain-word', 'content' => 'DomainException: unrelated validation'];
        $tags[] = ['entry_uuid' => 'keep', 'tag' => 'keep'];
        $tags[] = ['entry_uuid' => 'keep-domain-word', 'tag' => 'keep'];
        $db->table('telescope_entries')->insert($entries);
        $db->table('telescope_entries_tags')->insert($tags);
        $schema->create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
        });
        $db->table('cache')->insert([
            ['key' => 'unrelated-domainException', 'value' => 'keep'],
            ['key' => 'prefix-domain_info_example', 'value' => 'retired'],
            ['key' => 'prefix-domain-operation:example.test', 'value' => 'retired'],
        ]);
    }
}
