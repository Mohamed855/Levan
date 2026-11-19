<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TenantSidebarBrandingTest extends TestCase
{
    public function test_tenant_vertical_menu_does_not_hardcode_logo_path(): void
    {
        $content = file_get_contents(resource_path('views/tenant/layouts/sections/menu/verticalMenu.blade.php'));

        $this->assertStringNotContainsString(':logo-path="asset(\'static/brand/logos/logo-light.svg\')"', $content);
        $this->assertStringContainsString('<x-partials.tenant-logo :height="32"', $content);
    }

    public function test_tenant_vertical_menu_falls_back_to_office_name(): void
    {
        $content = file_get_contents(resource_path('views/tenant/layouts/sections/menu/verticalMenu.blade.php'));

        $this->assertStringContainsString("SettingsHelper::get('logo_text')", $content);
        $this->assertStringContainsString("SettingsHelper::get('office_name')", $content);
        $this->assertStringContainsString("config('app.name')", $content);
    }

    public function test_tenant_logo_component_preserves_an_explicit_logo(): void
    {
        $html = Blade::render('<x-partials.tenant-logo logo-path="https://example.test/client-logo.png" :height="32" />');
        $this->assertStringContainsString('src="https://example.test/client-logo.png"', $html);
        $this->assertStringContainsString('height="32"', $html);
    }

    public function test_main_settings_request_allows_webp_images(): void
    {
        $request = new MainSettingsRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('image', $rules);
        $this->assertStringContainsString('webp', $rules['image']);
    }
}
