<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Admin\AuctionTemplates\AuctionTemplateFieldDefinitionService;
use Tests\TestCase;

class AuctionTemplateFieldDefinitionsTest extends TestCase
{
    public function test_it_normalizes_image_field_zones_using_relative_coordinates(): void
    {
        $definitions = app(AuctionTemplateFieldDefinitionService::class)->normalize([
            [
                'key' => 'auction_name',
                'page' => 1,
                'x' => 0.1,
                'y' => 0.2,
                'width' => 0.5,
                'height' => 0.1,
            ],
        ]);

        $this->assertSame([[
            'key' => 'auction_name',
            'page' => 1,
            'x' => 0.1,
            'y' => 0.2,
            'width' => 0.5,
            'height' => 0.1,
        ]], $definitions);
    }
}
