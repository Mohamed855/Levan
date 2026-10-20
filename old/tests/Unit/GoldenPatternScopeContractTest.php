<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GoldenPatternScopeContractTest extends TestCase
{
    public function test_manifest_distinguishes_the_41_remaining_administrations_from_the_three_closed_tenant_administrations(): void
    {
        $manifest = file_get_contents(dirname(__DIR__, 2).'/docs/baselines/golden-pattern-manifest.md');

        $this->assertStringContainsString('41 remaining administrations', $manifest);
        $this->assertStringContainsString('Assignments, Properties, and Auctions', $manifest);
        $this->assertStringContainsString('44 administration families', $manifest);
    }
}
