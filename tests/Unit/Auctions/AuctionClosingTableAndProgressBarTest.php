<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\DataTables\Tenants\AuctionClosings\AuctionClosingsDataTable;
use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use Tests\TestCase;

class AuctionClosingTableAndProgressBarTest extends TestCase
{
    public function test_progress_bar_component_color_thresholds_and_label(): void
    {
        // 0/6 (0%) => yellow (bg-warning)
        $html0 = view('components.shared.ui.progress-bar', ['value' => 0, 'total' => 6])->render();
        $this->assertStringContainsString('0/6', $html0);
        $this->assertStringContainsString('bg-warning', $html0);

        // 2/6 (33.33%) => yellow (bg-warning)
        $html2 = view('components.shared.ui.progress-bar', ['value' => 2, 'total' => 6])->render();
        $this->assertStringContainsString('2/6', $html2);
        $this->assertStringContainsString('bg-warning', $html2);

        // 3/6 (50.00%) => blue (bg-info)
        $html3 = view('components.shared.ui.progress-bar', ['value' => 3, 'total' => 6])->render();
        $this->assertStringContainsString('3/6', $html3);
        $this->assertStringContainsString('bg-info', $html3);

        // 4/6 (66.67%) => green (bg-success)
        $html4 = view('components.shared.ui.progress-bar', ['value' => 4, 'total' => 6])->render();
        $this->assertStringContainsString('4/6', $html4);
        $this->assertStringContainsString('bg-success', $html4);

        // 5/6 (83.33%) => green (bg-success)
        $html5 = view('components.shared.ui.progress-bar', ['value' => 5, 'total' => 6])->render();
        $this->assertStringContainsString('5/6', $html5);
        $this->assertStringContainsString('bg-success', $html5);

        // 6/6 (100%) => green (bg-success)
        $html6 = view('components.shared.ui.progress-bar', ['value' => 6, 'total' => 6])->render();
        $this->assertStringContainsString('6/6', $html6);
        $this->assertStringContainsString('bg-success', $html6);

        // Edge case: 0/0 => yellow (bg-warning) and no division by zero
        $htmlZero = view('components.shared.ui.progress-bar', ['value' => 0, 'total' => 0])->render();
        $this->assertStringContainsString('0/0', $htmlZero);
        $this->assertStringContainsString('bg-warning', $htmlZero);
    }

    public function test_auctions_data_table_has_city_column_after_issuing_entity(): void
    {
        $dataTable = new AuctionsDataTable;
        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('getColumns');
        $method->setAccessible(true);
        $columns = $method->invoke($dataTable);

        $keys = array_column($columns, 'data');
        $issuingEntityIndex = array_search('issuing_entity', $keys, true);
        $cityIndex = array_search('city', $keys, true);

        $this->assertNotFalse($issuingEntityIndex, 'issuing_entity column not found');
        $this->assertNotFalse($cityIndex, 'city column not found');
        $this->assertSame($issuingEntityIndex + 1, $cityIndex, 'city column must be placed directly after issuing_entity');

        $cityCol = $columns[$cityIndex];
        $this->assertSame('المدينة', $cityCol['title']);
    }

    public function test_auction_closings_data_table_structure_and_columns(): void
    {
        $dataTable = new AuctionClosingsDataTable;
        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('getColumns');
        $method->setAccessible(true);
        $columns = $method->invoke($dataTable);

        $keys = array_column($columns, 'data');

        // Column # (id) is deleted completely
        $this->assertNotContains('id', $keys);

        // assignment_number is placed after name
        $nameIndex = array_search('name', $keys, true);
        $asgIndex = array_search('assignment_number', $keys, true);
        $this->assertNotFalse($nameIndex);
        $this->assertNotFalse($asgIndex);
        $this->assertSame($nameIndex + 1, $asgIndex, 'assignment_number must be directly after name');

        // city is present
        $this->assertContains('city', $keys);

        // properties_results is placed after end_date
        $endDateIndex = array_search('end_date', $keys, true);
        $propResultsIndex = array_search('properties_results', $keys, true);
        $this->assertNotFalse($endDateIndex);
        $this->assertNotFalse($propResultsIndex);
        $this->assertSame($endDateIndex + 1, $propResultsIndex, 'properties_results must be directly after end_date');

        $propResultsCol = $columns[$propResultsIndex];
        $this->assertSame('نتائج العقارات', $propResultsCol['title']);

        // closing_model is present and titled 'نموذج الإقفال'
        $closingModelIndex = array_search('closing_model', $keys, true);
        $this->assertNotFalse($closingModelIndex);
        $this->assertSame('نموذج الإقفال', $columns[$closingModelIndex]['title']);

        // closing_status is titled 'حالة الإقفال'
        $closingStatusIndex = array_search('closing_status', $keys, true);
        $this->assertNotFalse($closingStatusIndex);
        $this->assertSame('حالة الإقفال', $columns[$closingStatusIndex]['title']);
    }

    public function test_custom_column_callbacks_render_correctly(): void
    {
        $dataTable = new AuctionClosingsDataTable;
        $fakeDataTable = new class
        {
            public array $addedColumns = [];

            public array $editedColumns = [];

            public function addColumn(string $name, $callback): self
            {
                $this->addedColumns[$name] = $callback;

                return $this;
            }

            public function editColumn(string $name, $callback): self
            {
                $this->editedColumns[$name] = $callback;

                return $this;
            }
        };

        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('addCustomColumns');
        $method->setAccessible(true);
        $method->invoke($dataTable, $fakeDataTable);

        // Test closing_model callback
        $this->assertArrayHasKey('closing_model', $fakeDataTable->addedColumns);
        $dummyRow = new \stdClass;
        $dummyRow->id = 1;
        $closingModelHtml = ($fakeDataTable->addedColumns['closing_model'])($dummyRow);
        $this->assertStringNotContainsString('javascript:', $closingModelHtml);
        $this->assertStringContainsString('text-muted', $closingModelHtml);

        // Test properties_results callback
        $this->assertArrayHasKey('properties_results', $fakeDataTable->addedColumns);
        $dummyRow->sold_properties_count = 5;
        $dummyRow->properties_count = 6;
        $propResultsHtml = ($fakeDataTable->addedColumns['properties_results'])($dummyRow);
        $this->assertStringContainsString('5/6', $propResultsHtml);
        $this->assertStringContainsString('bg-success', $propResultsHtml);

        // Test assignment_number callback
        $this->assertArrayHasKey('assignment_number', $fakeDataTable->addedColumns);
        $dummyRow->assignment = (object) ['assignment_number' => 'ASG-9988'];
        $asgHtml = ($fakeDataTable->addedColumns['assignment_number'])($dummyRow);
        $this->assertStringContainsString('ASG-9988', $asgHtml);
        $this->assertStringContainsString('copyable-code', $asgHtml);
    }
}
