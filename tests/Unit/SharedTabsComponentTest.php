<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SharedTabsComponentTest extends TestCase
{
    public function test_renders_notification_filter_tabs_with_items_array(): void
    {
        $items = [
            [
                'label' => 'الكل',
                'icon' => 'ti ti-bell',
                'url' => '/notifications?filter=all',
                'active' => true,
                'count' => 15,
                'countAttributes' => ['data-filter-all-count' => true, 'aria-live' => 'polite'],
            ],
            [
                'label' => 'غير المقروءة',
                'icon' => 'ti ti-mail',
                'url' => '/notifications?filter=unread',
                'active' => false,
                'count' => 5,
                'countAttributes' => ['data-filter-unread-count' => true],
            ],
        ];

        $html = Blade::render(
            '<x-shared.ui.tabs :items="$items" aria-label="فلاتر الإشعارات" />',
            ['items' => $items]
        );

        $this->assertStringContainsString('class="btn-group', $html);
        $this->assertStringContainsString('role="group"', $html);
        $this->assertStringContainsString('aria-label="فلاتر الإشعارات"', $html);
        $this->assertStringContainsString('href="/notifications?filter=all"', $html);
        $this->assertStringContainsString('btn-primary', $html);
        $this->assertStringContainsString('btn-outline-primary', $html);
        $this->assertStringContainsString('data-filter-all-count', $html);
        $this->assertStringContainsString('data-filter-unread-count', $html);
        $this->assertStringContainsString('notification-filter__count', $html);
        $this->assertStringContainsString('15', $html);
        $this->assertStringContainsString('5', $html);
    }

    public function test_renders_bootstrap_tab_triggers_for_in_page_switching(): void
    {
        $items = [
            [
                'label' => 'الإعدادات العامة',
                'icon' => 'ti ti-settings',
                'target' => '#generalSettings',
                'active' => true,
            ],
            [
                'label' => 'إعدادات الطباعة',
                'icon' => 'ti ti-printer',
                'target' => '#printSettings',
                'active' => false,
            ],
        ];

        $html = Blade::render(
            '<x-shared.ui.tabs :items="$items" :vertical="true" aria-label="إعدادات النظام" />',
            ['items' => $items]
        );

        $this->assertStringContainsString('class="btn-group-vertical', $html);
        $this->assertStringContainsString('data-bs-toggle="tab"', $html);
        $this->assertStringContainsString('data-bs-target="#generalSettings"', $html);
        $this->assertStringContainsString('data-bs-target="#printSettings"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('aria-controls="generalSettings"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('aria-selected="false"', $html);
    }

    public function test_renders_via_tab_item_slot_components(): void
    {
        $html = Blade::render(
            '<x-shared.ui.tabs aria-label="تجربة">' .
            '<x-shared.ui.tab-item url="/test" :active="true" icon="ti ti-check" label="تجربة 1" :count="3" />' .
            '<x-shared.ui.tab-item target="#test2" :active="false" icon="ti ti-star" label="تجربة 2" />' .
            '</x-shared.ui.tabs>'
        );

        $this->assertStringContainsString('href="/test"', $html);
        $this->assertStringContainsString('btn-primary', $html);
        $this->assertStringContainsString('data-bs-target="#test2"', $html);
        $this->assertStringContainsString('data-bs-toggle="tab"', $html);
        $this->assertStringContainsString('3', $html);
    }
}
