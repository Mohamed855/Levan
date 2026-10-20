<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ResourcesBoundaryContractTest extends TestCase
{
    public function test_resources_follow_the_boundary_structure(): void
    {
        $resources = dirname(__DIR__, 2).'/resources';

        foreach ([
            'assets/js/admin',
            'assets/js/tenant',
            'assets/js/web',
            'assets/js/shared',
            'assets/scss/admin',
            'assets/scss/tenant',
            'assets/scss/web',
            'assets/scss/shared',
            'assets/css/tenant',
            'assets/css/web',
            'assets/css/shared',
            'assets/menu/tenant',
            'views/admin',
            'views/tenant',
            'views/web',
            'views/components',
            'views/emails',
            'views/errors',
        ] as $directory) {
            $this->assertDirectoryExists($resources.'/'.$directory, "{$directory} must exist.");
        }

        foreach ([
            'js',
            'scss',
            'css',
            'menu',
            'views/site',
            'views/tenants',
        ] as $legacyDirectory) {
            $this->assertDirectoryDoesNotExist($resources.'/'.$legacyDirectory, "{$legacyDirectory} must be removed.");
        }
    }
}
