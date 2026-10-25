<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class TenantPropertyImageOverlayContractTest extends TestCase
{
    public function test_property_image_selection_dims_the_unselected_area_with_a_half_opacity_black_overlay(): void
    {
        $source = file_get_contents(base_path('resources/assets/js/tenant/properties/create.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString("rgba(0, 0, 0, 0.5)", $source);
        $this->assertStringContainsString("fill('evenodd')", $source);
        $this->assertStringContainsString('drawOutsideSelectionOverlay(ctx, canvas.width, canvas.height);', $source);
        $this->assertStringContainsString('drawOutsideSelectionOverlay(oCtx, naturalW, naturalH);', $source);
    }
}
