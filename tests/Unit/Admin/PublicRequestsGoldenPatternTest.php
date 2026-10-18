<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\DataTables\Admin\PublicRequests\ContactRequestsDataTable;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use PHPUnit\Framework\TestCase;

final class PublicRequestsGoldenPatternTest extends TestCase
{
    public function test_public_request_data_tables_expose_the_shared_index_contract(): void
    {
        foreach ([ContactRequestsDataTable::class, TrialRequestsDataTable::class] as $dataTableClass) {
            $reflection = new \ReflectionClass($dataTableClass);

            self::assertTrue($reflection->hasMethod('getColumns'));
            self::assertTrue($reflection->hasMethod('applyCustomFilters'));
            self::assertTrue($reflection->hasMethod('buttons'));
        }
    }

    public function test_public_request_views_use_shared_index_and_form_components(): void
    {
        $root = dirname(__DIR__, 3);
        $contactIndex = file_get_contents($root . '/resources/views/admin/public_requests/contact/index.blade.php');
        $trialIndex = file_get_contents($root . '/resources/views/admin/public_requests/trial/index.blade.php');
        $contactShow = file_get_contents($root . '/resources/views/admin/public_requests/contact/show.blade.php');
        $trialShow = file_get_contents($root . '/resources/views/admin/public_requests/trial/show.blade.php');

        foreach ([$contactIndex, $trialIndex] as $indexView) {
            self::assertStringContainsString('<x-ui.stat-card', $indexView);
            self::assertStringContainsString('<x-data-table.filters', $indexView);
            self::assertStringContainsString('type="date"', $indexView);
            self::assertStringContainsString('data-filter-key="date_from"', $indexView);
            self::assertStringContainsString('data-filter-key="date_to"', $indexView);
            self::assertStringContainsString('$dataTable->table', $indexView);
        }

        self::assertSame(1, substr_count($contactIndex, '<x-filter.select'));
        self::assertSame(1, substr_count($trialIndex, '<x-filter.select'));

        self::assertStringContainsString('<x-form.controls.textarea', $contactShow);
        self::assertStringContainsString('<x-form.controls.textarea', $trialShow);
    }

    public function test_date_filters_use_date_inputs_across_canonical_tenant_pages(): void
    {
        $root = dirname(__DIR__, 3);
        $views = [
            $root . '/resources/views/tenants/assignments/index.blade.php',
            $root . '/resources/views/tenants/auctions/index.blade.php',
            $root . '/resources/views/tenants/reports/executive/index.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents($view);

            self::assertStringContainsString('type="date"', $contents, $view);
            self::assertStringNotContainsString('x-filter.select id="filter-start-date"', $contents, $view);
            self::assertStringNotContainsString('x-filter.select id="filter-end-date"', $contents, $view);
        }
    }
}
