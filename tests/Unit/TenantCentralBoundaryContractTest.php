<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TenantCentralBoundaryContractTest extends TestCase
{
    public function test_tenant_code_imports_central_models_only_inside_explicit_shared_gateways(): void
    {
        $appPath = dirname(__DIR__, 2).'/app';

        foreach ([
            $appPath.'/Http/Controllers/Tenants',
            $appPath.'/Actions/Tenants',
            $appPath.'/Jobs/Tenants',
            $appPath.'/Services/Tenants',
        ] as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $path = str_replace('\\', '/', $file->getPathname());
                $source = file_get_contents($file->getPathname());

                if (str_contains($path, '/Services/Tenants/Shared/Central')) {
                    $this->assertStringContainsString('Tenancy::central(', $source, "{$path} must make its central context explicit.");

                    continue;
                }

                $this->assertStringNotContainsString(
                    'App\\Models\\Admin',
                    $source,
                    "Tenant code must cross the central boundary through an explicit gateway: {$path}"
                );
                $this->assertStringNotContainsString(
                    "DB::connection('mysql')",
                    $source,
                    "Tenant code must not query the central connection directly: {$path}"
                );
            }
        }
    }

    public function test_tenant_domain_lookup_uses_the_tenant_controller_and_explicit_central_gateway(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $source = file_get_contents($projectRoot.'/app/Http/Controllers/Tenants/Domains/DomainLookupController.php');
        $routes = file_get_contents($projectRoot.'/routes/tenant.php');

        $this->assertStringContainsString('CentralDomainGateway', $source);
        $this->assertStringContainsString("middleware('can:domain.view')", $source);
        $this->assertStringContainsString('findTransferred($domain)', $source);
        $this->assertStringNotContainsString('App\\Http\\Controllers\\Admin\\APILayer\\APILayerController', $routes);
        $this->assertStringContainsString('DomainLookupController::class', $routes);
    }
}
