<?php

declare(strict_types=1);

namespace Tests\Unit\DataTables;

use App\DataTables\Tenants\AuctionClosings\AuctionClosingsDataTable;
use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\DataTables\Tenants\MarketingCampaigns\MarketingCampaignsDataTable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class IssuingEntityColumnPlainTextTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string}>
     */
    public static function auctionBasedDataTablesProvider(): array
    {
        return [
            'auctions' => [AuctionsDataTable::class],
            'auction closings' => [AuctionClosingsDataTable::class],
            'marketing campaigns' => [MarketingCampaignsDataTable::class],
        ];
    }

    /**
     * @param  class-string  $dataTableClass
     */
    #[DataProvider('auctionBasedDataTablesProvider')]
    public function test_issuing_entity_column_is_not_raw_html_and_renders_plain_text_without_assignment_link(
        string $dataTableClass,
    ): void {
        $dataTable = new $dataTableClass;
        $reflection = new \ReflectionClass($dataTable);

        $rawColumnsMethod = $reflection->getMethod('rawColumns');
        $rawColumnsMethod->setAccessible(true);
        $rawColumns = $rawColumnsMethod->invoke($dataTable);

        $this->assertNotContains(
            'issuing_entity',
            $rawColumns,
            "{$dataTableClass} must not keep issuing_entity in rawColumns() once rendered as plain text."
        );

        $collector = new class
        {
            /** @var array<string, callable> */
            public array $addedColumns = [];

            public function setRowId(callable $callback): self
            {
                return $this;
            }

            public function addColumn(string $name, callable $callback): self
            {
                $this->addedColumns[$name] = $callback;

                return $this;
            }

            public function editColumn(string $name, callable $callback): self
            {
                return $this;
            }
        };

        $addColumnsMethod = $reflection->getMethod('addCustomColumns');
        $addColumnsMethod->setAccessible(true);
        $addColumnsMethod->invoke($dataTable, $collector);

        $this->assertArrayHasKey('issuing_entity', $collector->addedColumns);
        $callback = $collector->addedColumns['issuing_entity'];

        $withRelation = (object) [
            'assignment_id' => 15,
            'assignment' => (object) [
                'name' => 'إسناد الرياض',
                'issuing_entity' => 'جهة قديمة',
                'issuingEntity' => (object) ['name' => 'وزارة العدل'],
            ],
        ];
        $relationResult = $callback($withRelation);
        $this->assertSame('وزارة العدل', $relationResult);
        $this->assertStringNotContainsString('<a', $relationResult);
        $this->assertStringNotContainsString('href', $relationResult);
        $this->assertStringNotContainsString('assignments', $relationResult);

        $withLegacyString = (object) [
            'assignment_id' => 16,
            'assignment' => (object) [
                'name' => 'إسناد جدة',
                'issuing_entity' => 'محكمة التنفيذ',
                'issuingEntity' => null,
            ],
        ];
        $this->assertSame('محكمة التنفيذ', $callback($withLegacyString));

        $withAssignmentNameFallback = (object) [
            'assignment_id' => 17,
            'assignment' => (object) [
                'name' => 'إسناد الدمام',
                'issuing_entity' => '',
                'issuingEntity' => null,
            ],
        ];
        $this->assertSame('إسناد الدمام', $callback($withAssignmentNameFallback));

        $withoutAssignment = (object) [
            'assignment_id' => null,
            'assignment' => null,
        ];
        $this->assertSame('-', $callback($withoutAssignment));
    }
}
