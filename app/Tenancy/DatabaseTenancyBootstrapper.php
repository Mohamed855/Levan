<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class DatabaseTenancyBootstrapper implements TenancyBootstrapper
{
    protected static $currentTenantId = null;

    public function bootstrap(Tenant $tenant)
    {
        if (static::$currentTenantId === $tenant->getTenantKey() && Config::get('database.connections.tenant.host')) {
            return;
        }

        $cacheKey = "tenant_connection_{$tenant->getTenantKey()}";

        $connectionConfig = Cache::get($cacheKey);

        if (! is_array($connectionConfig) || empty($connectionConfig['host']) || empty($connectionConfig['database'])) {
            if (empty($tenant->db_host) || empty($tenant->db_database)) {
                $fresh = $tenant->fresh();
                if ($fresh) {
                    $tenant = $fresh;
                }
            }

            $host = $tenant->db_host ?: config('database.connections.mysql.host', '127.0.0.1');
            $database = $tenant->db_database;

            $connectionConfig = [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $tenant->db_port ?? config('database.connections.mysql.port', '3306'),
                'database' => $database,
                'username' => $tenant->db_username ?? config('database.connections.mysql.username', 'root'),
                'password' => $tenant->db_password ?? config('database.connections.mysql.password', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'unix_socket' => env('DB_SOCKET') ?: config('database.connections.mysql.unix_socket', ''),
                'options' => [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            ];

            if (! empty($connectionConfig['host']) && ! empty($connectionConfig['database'])) {
                Cache::put($cacheKey, $connectionConfig, 3600);
            }
        }

        // دائماً أزل الاتصال القديم قبل التبديل لأي tenant
        DB::purge('tenant');

        Config::set('database.connections.tenant', $connectionConfig);
        Config::set('database.default', 'tenant');
        DB::setDefaultConnection('tenant');

        // دائماً أنشئ اتصالاً جديداً بعد تحديث الـ config
        DB::reconnect('tenant');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        static::$currentTenantId = $tenant->getTenantKey();
    }

    public function revert()
    {
        // نظّف اتصال tenant حتى لا يتسرب لوظيفة queue أخرى
        DB::purge('tenant');

        Config::set('database.default', 'mysql');
        DB::setDefaultConnection('mysql');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        static::$currentTenantId = null;
    }
}
