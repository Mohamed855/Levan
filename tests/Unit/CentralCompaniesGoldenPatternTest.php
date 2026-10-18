<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CentralCompaniesGoldenPatternTest extends TestCase
{
    public function test_companies_index_uses_the_five_card_and_filter_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $index = file_get_contents($root.'/resources/views/admin/companies/index.blade.php');
        $dataTable = file_get_contents($root.'/app/DataTables/Admin/Companies/CompanyDataTable.php');

        self::assertSame(5, substr_count($index, '<x-ui.stat-card'));
        self::assertSame(5, substr_count($index, 'col="col-sm-6 col-xl"'));
        self::assertStringContainsString('data-filter-key="created_from"', $index);
        self::assertStringContainsString('data-filter-key="created_to"', $index);
        self::assertStringContainsString("'data' => 'checkbox'", $dataTable);
        self::assertStringContainsString("'className' => 'btn btn-export'", $dataTable);
        self::assertStringContainsString("'text' => 'حذف المحدد'", $dataTable);
        self::assertStringContainsString("'data' => 'subscription_status'", $dataTable);
    }
}
