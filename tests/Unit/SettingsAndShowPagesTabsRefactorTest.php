<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class SettingsAndShowPagesTabsRefactorTest extends TestCase
{
    public function test_tenant_settings_has_no_tabs_and_uses_single_card_form(): void
    {
        $content = file_get_contents(resource_path('views/tenant/settings/settings.blade.php'));

        $this->assertStringNotContainsString('<x-shared.ui.tabs', $content);
        $this->assertStringNotContainsString('class="nav nav-tabs', $content);
        $this->assertStringNotContainsString('tab.css', $content);
        $this->assertStringNotContainsString('tab-pane', $content);
        $this->assertStringContainsString('name="operational_regions"', $content);
        $this->assertStringContainsString('formGeneralSettings', $content);
    }

    public function test_admin_settings_has_no_tabs_and_uses_single_card_form(): void
    {
        $content = file_get_contents(resource_path('views/admin/settings/settings.blade.php'));

        $this->assertStringNotContainsString('<x-shared.ui.tabs', $content);
        $this->assertStringNotContainsString('class="nav nav-tabs', $content);
        $this->assertStringNotContainsString('tab.css', $content);
        $this->assertStringNotContainsString('tab-pane', $content);
        $this->assertStringContainsString('formGeneralSettings', $content);
    }

    public function test_admin_plans_show_has_no_tabs_and_uses_stacked_cards(): void
    {
        $content = file_get_contents(resource_path('views/admin/plans/show.blade.php'));

        $this->assertStringNotContainsString('nav-tabs', $content);
        $this->assertStringNotContainsString('tab-content', $content);
        $this->assertStringNotContainsString('tab-pane', $content);
        $this->assertStringNotContainsString('data-bs-toggle="tab"', $content);
        $this->assertStringNotContainsString('tab.css', $content);
        $this->assertStringNotContainsString('tab.js', $content);
        $this->assertStringContainsString('col-xl-4 col-lg-5 col-md-5', $content);
        $this->assertStringContainsString('col-xl-8 col-lg-7 col-md-7', $content);
        $this->assertStringContainsString('تفاصيل الباقة', $content);
        $this->assertStringContainsString('ميزات الباقة التسويقية', $content);
        $this->assertStringContainsString('الاشتراكات والشركات المرتبطة بالباقة', $content);
    }

    public function test_admin_coupons_details_has_no_tabs_and_uses_stacked_cards(): void
    {
        $content = file_get_contents(resource_path('views/admin/coupons/details.blade.php'));

        $this->assertStringNotContainsString('nav-tabs', $content);
        $this->assertStringNotContainsString('tab-content', $content);
        $this->assertStringNotContainsString('tab-pane', $content);
        $this->assertStringNotContainsString('data-bs-toggle="tab"', $content);
        $this->assertStringNotContainsString('tab.css', $content);
        $this->assertStringNotContainsString('tab.js', $content);
        $this->assertStringContainsString('col-xl-4 col-lg-5 col-md-5', $content);
        $this->assertStringContainsString('col-xl-8 col-lg-7 col-md-7', $content);
        $this->assertStringContainsString('تفاصيل الكوبون', $content);
        $this->assertStringContainsString('سجل استخدام الكوبون', $content);
    }

    public function test_tenant_settings_center_has_been_removed(): void
    {
        $this->assertFalse(file_exists(resource_path('views/tenant/settings/center.blade.php')));
    }
}
