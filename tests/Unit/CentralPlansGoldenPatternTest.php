<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class CentralPlansGoldenPatternTest extends TestCase
{
    public function test_plans_index_uses_a_consistent_five_card_datatable_page(): void
    {
        $dataTable = file_get_contents(app_path('DataTables/Admin/Plans/PlanDataTable.php'));
        $index = file_get_contents(resource_path('views/admin/plans/index.blade.php'));
        $script = file_get_contents(resource_path('assets/js/admin/plans/index.js'));

        self::assertStringContainsString("return 'plans-table'", $dataTable);
        self::assertStringContainsString('x-data-table.page', $index);
        self::assertSame(5, substr_count($index, '<x-ui.stat-card'));
        self::assertSame(5, substr_count($index, 'col="col-sm-6 col-xl"'));
        self::assertStringContainsString('#plans-table', $index);
        self::assertStringContainsString('#plans-table', $script);
        self::assertStringNotContainsString('paln-table', $index.$script);
    }

    public function test_plan_datatable_eager_loads_subscription_counts_for_row_actions(): void
    {
        $dataTable = file_get_contents(app_path('DataTables/Admin/Plans/PlanDataTable.php'));
        $action = file_get_contents(resource_path('views/admin/plans/action.blade.php'));

        self::assertStringContainsString("->withCount('subscriptions')", $dataTable);
        self::assertStringContainsString('$row->subscriptions_count > 0', $action);
        self::assertStringNotContainsString('$row->hasSubscriptions()', $action);
    }
}
