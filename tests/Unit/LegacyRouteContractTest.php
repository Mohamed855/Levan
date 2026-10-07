<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LegacyRouteContractTest extends TestCase
{
    public function test_route_files_do_not_keep_commented_route_definitions(): void
    {
        $routesPath = dirname(__DIR__, 2) . '/routes';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($routesPath));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/^\s*\/\/\s*Route::/m',
                file_get_contents($file->getPathname()),
                "{$file->getPathname()} must register a route or document it in the Phase 8 registry, not leave it commented out.",
            );
        }
    }
}
