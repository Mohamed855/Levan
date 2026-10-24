<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class TestDatabaseGuard
{
    public static function assertSafe(string $environment, array $database, array $tenancy): void
    {
        if ($environment !== 'testing') {
            throw new RuntimeException('Database tests require APP_ENV=testing.');
        }

        if (($tenancy['prefix'] ?? null) !== 'levan_test_tenant_') {
            throw new RuntimeException('Database tests require the isolated levan_test_tenant_ prefix.');
        }

        $connectionNames = array_unique(array_filter([
            $database['default'] ?? null,
            $tenancy['central_connection'] ?? null,
            $tenancy['template_tenant_connection'] ?? null,
        ], static fn ($name): bool => $name !== null));

        if ($connectionNames === [] || empty($database['default']) || empty($tenancy['central_connection'])) {
            throw new RuntimeException('Database tests require explicit default and central connections.');
        }

        foreach ($connectionNames as $name) {
            $connection = $database['connections'][$name] ?? [];
            $driver = $connection['driver'] ?? null;
            $databaseName = $connection['database'] ?? null;

            if (! empty($connection['url'])) {
                throw new RuntimeException('Test database URLs are refused; configure explicit test connection fields.');
            }

            if ($driver === 'sqlite' && $databaseName === ':memory:') {
                continue;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)
                && is_string($databaseName)
                && preg_match('/\Alevan_test_central(?:_[a-zA-Z0-9]+)?\z/', $databaseName) === 1) {
                continue;
            }

            throw new RuntimeException('Unsafe test database configuration refused for connection: '.(string) $name);
        }
    }
}
