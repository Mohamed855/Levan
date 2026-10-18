<?php

namespace App\DataTables\Tenants\Reports\Performance;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenants\Reports\Performance\EmployeePerformanceService;
use App\Support\ReportNumberFormatter;

class EmployeePerformanceDataTable extends AbstractDataTable
{
    private $route = 'reports.employees';

    protected EmployeePerformanceService $employeePerformanceService;

    public function __construct(EmployeePerformanceService $employeePerformanceService)
    {
        $this->employeePerformanceService = $employeePerformanceService;
    }

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
        return 'employee-performance-table';
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

        $employeesPerformance = $this->employeePerformanceService->getEmployeesPerformance($filters);

        return collect($employeesPerformance);
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'employee_name',             'name' => 'employee_name',              'title' => 'اسم الموظف'],
            ['data' => 'customers_count',           'name' => 'customers_count',            'title' => 'عدد العملاء'],
            ['data' => 'total_portfolio',           'name' => 'total_portfolio',            'title' => 'إجمالي المحفظة'],
            ['data' => 'avg_payment',               'name' => 'avg_payment',                'title' => 'متوسط السداد'],
            ['data' => 'paid_customers',            'name' => 'paid_customers',             'title' => 'عدد العملاء المسددين'],
            ['data' => 'avg_calls',                 'name' => 'avg_calls',                  'title' => 'متوسط المكالمات'],
            ['data' => 'not_contacted',             'name' => 'not_contacted',              'title' => 'لم يتم التواصل'],
            ['data' => 'overdue_tasks',             'name' => 'overdue_tasks',              'title' => 'المهام المتأخرة'],
            ['data' => 'promises_count',            'name' => 'promises_count',             'title' => 'وعد بالسداد'],
            ['data' => 'promises_amount',           'name' => 'promises_amount',            'title' => 'مبلغ الوعد بالسداد'],
            ['data' => 'promise_achievement_rate',  'name' => 'promise_achievement_rate',   'title' => 'نسبة تحقيق الوعد'],
            ['data' => 'target_achievement_rate',   'name' => 'target_achievement_rate',    'title' => 'نسبة تحقيق المستهدف'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Columns
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('company_name', function ($row) {
                return '<div class="fw-semibold">'.e($row['company_name']).'</div>';
            })

            ->editColumn('employee_name', function ($row) {
                return '<div class="fw-semibold text-primary">'.e($row['employee_name']).'</div>';
            })

            ->editColumn('customers_count', function ($row) {
                return '<span class="badge bg-label-info">'.$this->formatNumber($row['customers_count']).'</span>';
            })

            ->editColumn('total_portfolio', function ($row) {
                return '<span class="text-primary fw-bold">'.number_format($this->formatNumber($row['total_portfolio'])).'</span>';
            })

            ->editColumn('avg_payment', function ($row) {
                return '<span class="text-success">'.number_format($this->formatNumber($row['avg_payment'])).'</span>';
            })

            ->editColumn('paid_customers', function ($row) {
                return '<span class="badge bg-label-success">'.number_format($this->formatNumber($row['paid_customers'])).'</span>';
            })

            ->editColumn('avg_calls', function ($row) {
                return '<span class="badge bg-secondary">'.number_format($this->formatNumber($row['avg_calls'])).'</span>';
            })

            ->editColumn('not_contacted', function ($row) {
                return '<span class="badge bg-label-warning">'.number_format($this->formatNumber($row['not_contacted'])).'</span>';
            })

            ->editColumn('overdue_tasks', function ($row) {
                return '<span class="badge bg-label-danger">'.number_format($this->formatNumber($row['overdue_tasks'] ?? 0)).'</span>';
            })

            ->editColumn('promises_count', function ($row) {
                return '<span class="badge bg-label-info">'.number_format($this->formatNumber($row['promises_count'])).'</span>';
            })

            ->editColumn('promises_amount', function ($row) {
                return '<span class="text-warning fw-bold">'.number_format($this->formatNumber($row['promises_amount'])).'</span>';
            })

            ->editColumn('promise_achievement_rate', function ($row) {
                $rate = (float) $row['promise_achievement_rate'];
                $badgeClass = $rate >= 80 ? 'bg-label-success' : ($rate >= 50 ? 'bg-label-warning' : 'bg-label-danger');

                return '<span class="badge '.$badgeClass.'">'.$this->formatNumber($rate).'%</span>';
            })

            ->editColumn('target_achievement_rate', function ($row) {
                $rate = (float) $row['target_achievement_rate'];
                $badgeClass = $rate >= 100 ? 'bg-label-success' : ($rate >= 50 ? 'bg-label-warning' : 'bg-label-danger');

                return '<span class="badge '.$badgeClass.'">'.$this->formatNumber($rate).'%</span>';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Format Currency
    |--------------------------------------------------------------------------
    */
    private function formatNumber($amount): string
    {
        return ReportNumberFormatter::format($amount);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'company_name',
            'employee_name',
            'customers_count',
            'total_portfolio',
            'avg_payment',
            'paid_customers',
            'avg_calls',
            'not_contacted',
            'overdue_tasks',
            'promises_count',
            'promises_amount',
            'promise_achievement_rate',
            'target_achievement_rate',
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
                'action' => 'function() { exportEmployeesToExcel(); }',
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
