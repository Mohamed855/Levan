<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Support\Auctions\TemplateSchema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TemplateSchemaBackgroundTest extends TestCase
{
    public function test_backgrounds_are_normalized_to_cover_with_centered_position(): void
    {
        $schema = TemplateSchema::sample('social');
        $schema['pages'][0]['background']['fit'] = 'contain';
        unset(
            $schema['pages'][0]['background']['position_x'],
            $schema['pages'][0]['background']['position_y'],
        );

        $background = app(TemplateSchema::class)->normalize($schema, 'social')['pages'][0]['background'];

        self::assertSame('cover', $background['fit']);
        self::assertSame(50, $background['position_x']);
        self::assertSame(50, $background['position_y']);
    }

    public function test_background_position_must_be_between_zero_and_one_hundred(): void
    {
        $schema = TemplateSchema::sample('social');
        $schema['pages'][0]['background']['position_x'] = -1;
        $schema['pages'][0]['background']['position_y'] = 101;

        $this->expectException(ValidationException::class);

        app(TemplateSchema::class)->validate($schema, 'social');
    }

    public function test_rendered_document_uses_cover_and_saved_background_position(): void
    {
        $schema = TemplateSchema::sample('social');
        $schema['pages'][0]['background'] = [
            'color' => '#ffffff',
            'image' => 'auction_templates/1/versions/1/background.webp',
            'fit' => 'contain',
            'position_x' => 25,
            'position_y' => 80,
        ];

        $html = view('shared.auctions.design-document', [
            'schema' => $schema,
            'bindings' => [],
        ])->render();

        self::assertStringContainsString('background-size:cover', $html);
        self::assertStringContainsString('background-position:25% 80%', $html);
        self::assertStringNotContainsString('background-size:contain', $html);
    }
}
