<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTables\Abstract\AbstractDataTable;
use App\DataTables\Admin\ActivityLog\AdminActivityLogDataTable;
use App\DataTables\Admin\AdminMangment\Users\AdminUserDataTable as ActiveAdminUserDataTable;
use App\DataTables\Admin\Archive\AdminUser\AdminUserDataTable as ArchivedAdminUserDataTable;
use App\DataTables\Admin\Archive\Bill\BillDataTable as ArchivedBillDataTable;
use App\DataTables\Admin\Archive\Coupon\CouponDataTable as ArchivedCouponDataTable;
use App\DataTables\Admin\Archive\Plan\PlanDataTable as ArchivedPlanDataTable;
use App\DataTables\Admin\Archive\Tenant\TenantDataTable as ArchivedTenantDataTable;
use App\DataTables\Admin\Bill\AdminBillsDataTable;
use App\DataTables\Admin\Coupons\CouponDataTable;
use App\DataTables\Admin\PublicRequests\ContactRequestsDataTable;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\DataTables\Admin\Tickets\AdminTicketsDataTable;
use App\DataTables\Tenants\Archive\User\ArchivedUsersDataTable;
use App\DataTables\Tenants\Assignments\AssignmentArchiveDataTable;
use App\DataTables\Tenants\Auctions\AuctionArchiveDataTable;
use App\DataTables\Tenants\Properties\PropertyArchiveDataTable;
use App\DataTables\Tenants\Tickets\TicketsDataTable;
use Tests\TestCase;

final class DataTableSelectionAndExportTest extends TestCase
{
    public function test_updated_datatables_expose_checkbox_first_and_wire_selected_row_exports(): void
    {
        $classes = [
            TicketsDataTable::class,
            AdminTicketsDataTable::class,
            ContactRequestsDataTable::class,
            TrialRequestsDataTable::class,
            AssignmentArchiveDataTable::class,
            AuctionArchiveDataTable::class,
            PropertyArchiveDataTable::class,
            ArchivedAdminUserDataTable::class,
            ArchivedBillDataTable::class,
            ArchivedCouponDataTable::class,
            ArchivedPlanDataTable::class,
            ArchivedTenantDataTable::class,
            ArchivedUsersDataTable::class,
            ActiveAdminUserDataTable::class,
            AdminBillsDataTable::class,
            CouponDataTable::class,
            subscriptionMangmentDataTable::class,
            AdminActivityLogDataTable::class,
        ];

        foreach ($classes as $class) {
            /** @var AbstractDataTable $instance */
            $instance = app($class);

            $columnsMethod = new \ReflectionMethod($instance, 'getColumns');
            $columnsMethod->setAccessible(true);
            $columns = array_values($columnsMethod->invoke($instance));

            $this->assertSame('checkbox', $columns[0]['data'] ?? null, "{$class} must place checkbox first.");
            $this->assertFalse($columns[0]['orderable'] ?? true, "{$class} checkbox column must not be orderable.");

            $rawColumnsMethod = new \ReflectionMethod($instance, 'rawColumns');
            $rawColumnsMethod->setAccessible(true);
            $this->assertContains('checkbox', $rawColumnsMethod->invoke($instance), "{$class} must include checkbox in rawColumns.");

            $orderMethod = new \ReflectionMethod($instance, 'getCustomOrder');
            $orderMethod->setAccessible(true);
            $order = $orderMethod->invoke($instance);
            if ($order !== []) {
                $orderedCol = $columns[$order[0]] ?? null;
                $this->assertNotNull($orderedCol, "{$class} default order index {$order[0]} must exist.");
                $this->assertTrue($orderedCol['orderable'] ?? true, "{$class} default order column '{$orderedCol['data']}' must be orderable.");
            }

            $buttonsMethod = new \ReflectionMethod($instance, 'normalizedButtons');
            $buttonsMethod->setAccessible(true);
            $buttons = $buttonsMethod->invoke($instance);

            $collection = collect($buttons)->firstWhere('extend', 'collection');
            $this->assertIsArray($collection, "{$class} must define an الإجراءات collection dropdown.");

            $subButtons = collect($collection['buttons'] ?? []);
            foreach (['copy', 'excel'] as $extendType) {
                $exportButton = $subButtons->firstWhere('extend', $extendType);
                $this->assertIsArray($exportButton, "{$class} must include '{$extendType}' inside الإجراءات.");
                $this->assertStringContainsString(
                    '.row-checkbox:checked',
                    (string) ($exportButton['exportOptions']['rows'] ?? ''),
                    "{$class} '{$extendType}' must filter checked rows."
                );

                $exportedIndices = $exportButton['exportOptions']['columns'] ?? [];
                $this->assertNotEmpty($exportedIndices, "{$class} '{$extendType}' must export data columns.");

                foreach ($exportedIndices as $index) {
                    $colData = $columns[$index]['data'] ?? null;
                    $this->assertNotContains(
                        $colData,
                        ['checkbox', 'actions', 'action', 'image', 'icon', 'color'],
                        "{$class} '{$extendType}' must not export UI column '{$colData}'."
                    );
                }
            }

            $scripts = $instance->html()->generateScripts()->toHtml();
            $this->assertStringContainsString('.row-checkbox:checked', $scripts, "{$class} generated scripts must include selected-row filter callback.");
            $this->assertStringContainsString('"data":"checkbox"', $scripts, "{$class} generated scripts must include checkbox column.");
        }
    }

    public function test_tickets_datatable_renders_checkbox_column_and_falls_back_to_ticket_number(): void
    {
        $table = new TicketsDataTable;

        $rows = collect([
            (object) [
                'id' => 10,
                'ticket_number' => 'TKT-10',
                'title' => 'تذكرة محلية',
                'ticketing_sections_id' => 1,
                'status' => 'open',
                'created_at' => '2026-11-11T10:00:00Z',
            ],
            (object) [
                'ticket_number' => 'EXT-99',
                'title' => 'تذكرة خارجية',
                'ticketing_sections_id' => 1,
                'status' => 'open',
                'created_at' => '2026-11-11T10:00:00Z',
            ],
        ]);

        $response = $table->dataTable($rows)->toArray();

        $this->assertCount(2, $response['data']);
        $this->assertSame('<input type="checkbox" class="row-checkbox" value="10">', $response['data'][0]['checkbox']);
        $this->assertSame('<input type="checkbox" class="row-checkbox" value="EXT-99">', $response['data'][1]['checkbox']);
    }
}
