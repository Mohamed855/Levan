<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Support\Auctions\TemplateSchema;
use Tests\TestCase;

final class TemplateSchemaPresetsTest extends TestCase
{
    public function test_sample_presets_contains_all_supported_types(): void
    {
        $presets = TemplateSchema::samplePresets();

        self::assertCount(6, $presets);

        $brochurePresets = TemplateSchema::samplePresets('brochure');
        $billboardPresets = TemplateSchema::samplePresets('billboard');
        $socialPresets = TemplateSchema::samplePresets('social');

        self::assertCount(2, $brochurePresets);
        self::assertCount(2, $billboardPresets);
        self::assertCount(2, $socialPresets);
    }

    public function test_all_sample_presets_pass_schema_validation(): void
    {
        $validator = new TemplateSchema();
        $presets = TemplateSchema::samplePresets();

        foreach ($presets as $preset) {
            self::assertArrayHasKey('id', $preset);
            self::assertArrayHasKey('type', $preset);
            self::assertArrayHasKey('title', $preset);
            self::assertArrayHasKey('description', $preset);
            self::assertArrayHasKey('badge', $preset);
            self::assertArrayHasKey('schema', $preset);

            $validated = $validator->validate($preset['schema'], $preset['type']);
            self::assertIsArray($validated);
            self::assertNotEmpty($validated['pages']);
        }
    }
}
