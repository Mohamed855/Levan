<?php

namespace App\DataTables\Tenants\Reports\Performance;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenants\Reports\Performance\CompanyPerformanceService;

class CompanyPerformanceDataTable extends AbstractDataTable
{
    private $route = 'reports.performance';

    public function __construct(private CompanyPerformanceService $companyPerformanceService) {}

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->collection($query)->addIndexColumn();

        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Table Id
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return 'company-performance-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        $year = request('year');
        $month = request('month');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        if (! $dateFrom && $year && $month) {
            $paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
            $lastDay = date('t', strtotime("$year-$paddedMonth-01"));
            $dateFrom = "$year-$paddedMonth-01";
            $dateTo = "$year-$paddedMonth-$lastDay";
        }

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'company_id' => request('company_id'),
            'portfolio_id' => request('portfolio_id'),
            'team_id' => request('team_id'),
            'user_id' => request('user_id'),
        ];

        $companiesPerformance = $this->companyPerformanceService->getCompaniesPerformance($filters);

        return collect($companiesPerformance);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Columns - حذف total_collected
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'الشركة', 'className' => 'text-start'],
            ['data' => 'customers_count', 'name' => 'customers_count', 'title' => 'عدد العملاء', 'className' => 'text-center'],
            ['data' => 'total_portfolio', 'name' => 'total_portfolio', 'title' => 'إجمالي المديونية', 'className' => 'text-center'],
            ['data' => 'customer_target', 'name' => 'customer_target', 'title' => 'المستهدف', 'className' => 'text-center'],
            ['data' => 'total_payment', 'name' => 'total_payment', 'title' => 'إجمالي السداد', 'className' => 'text-center'],
            ['data' => 'total_settlements', 'name' => 'total_settlements', 'title' => 'إجمالي التسويات', 'className' => 'text-center'],
            // ✅ حذف عمود total_collected
            ['data' => 'achievement_rate', 'name' => 'achievement_rate', 'title' => 'نسبة الإنجاز', 'className' => 'text-center'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Custom Columns - حذف total_collected
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('company_name', function ($row) {
                return '<div class="fw-semibold">'.e($row['company_name']).'</div>';
            })

            ->editColumn('customers_count', function ($row) {
                return '<span class="badge bg-label-info">'.number_format($row['customers_count']).'</span>';
            })

            ->editColumn('total_portfolio', function ($row) {
                return '<span class="text-primary fw-bold">'.$this->formatCurrency($row['total_portfolio']).'</span>';
            })

            ->editColumn('customer_target', function ($row) {
                return '<span class="text-secondary">'.$this->formatCurrency($row['customer_target']).'</span>';
            })

            ->editColumn('total_payment', function ($row) {
                return '<span class="text-success fw-bold">'.$this->formatCurrency($row['total_payment']).'</span>';
            })

            ->editColumn('total_settlements', function ($row) {
                return '<span class="text-warning">'.$this->formatCurrency($row['total_settlements']).'</span>';
            })

            // ✅ حذف total_collected

            ->editColumn('achievement_rate', function ($row) {
                $percentage = number_format($row['achievement_rate'], 1);
                $badgeClass = $percentage >= 100 ? 'bg-label-success' : ($percentage >= 50 ? 'bg-label-warning' : 'bg-label-danger');

                return '<span class="badge '.$badgeClass.'">'.$percentage.'%</span>';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Format Currency
    |--------------------------------------------------------------------------
    */
    private function formatCurrency($amount): string
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1).'M';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 1).'K';
        }

        return number_format($amount, 0);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Helper Methods - حذف total_collected
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'company_name',
            'customers_count',
            'total_portfolio',
            'customer_target',
            'total_payment',
            'total_settlements',
            // ✅ حذف total_collected
            'achievement_rate',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        // Filters are already applied in the resource() method
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Dropdown Button
    |--------------------------------------------------------------------------
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            [
                'text' => 'تصدير Excel',
                'className' => 'btn-export-excel',
                'action' => 'function() { exportFilteredToExcel(); }',
            ],
            [
                'text' => 'تصدير PDF',
                'className' => 'btn-export-pdf',
                'action' => 'function() { exportCustomReportToPDF(); }',
            ],
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}
