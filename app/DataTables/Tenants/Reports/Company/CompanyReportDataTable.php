<?php

namespace App\DataTables\Tenants\Reports\Company;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyReportDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'companies-table';
    }

    public function ajax(): JsonResponse
    {
        return $this->dataTable($this->query())->toJson();
    }

    protected function resource(): Builder
    {
        $portfolioSummary = DB::table('portfolios')
            ->whereNull('deleted_at')
            ->groupBy('company_id')
            ->selectRaw("
                company_id,
                COUNT(*) as total_portfolios,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_portfolios,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_portfolios
            ");

        $customerSummary = DB::table('customers')
            ->whereNull('deleted_at')
            ->groupBy('company_id')
            ->selectRaw('company_id, COUNT(*) as customers_count');

        $financialSummary = DB::table('customers')
            ->leftJoin('customer_transactions', function ($join): void {
                $join->on('customer_transactions.customer_id', '=', 'customers.id')
                    ->whereNull('customer_transactions.deleted_at');
            })
            ->whereNull('customers.deleted_at')
            ->groupBy('customers.company_id')
            ->selectRaw("
                customers.company_id,
                SUM(CASE WHEN customer_transactions.type = 'debit' THEN customer_transactions.amount ELSE 0 END) as total_debt,
                SUM(CASE WHEN customer_transactions.type = 'credit' THEN customer_transactions.amount ELSE 0 END) as total_credit
            ");

        $targetSummary = DB::table('company_targets')
            ->whereNull('effective_to')
            ->groupBy('company_id')
            ->selectRaw('company_id, MAX(target_percentage) as target_percentage');

        return Company::query()
            ->leftJoinSub($portfolioSummary, 'portfolio_summary', 'portfolio_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($customerSummary, 'customer_summary', 'customer_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($financialSummary, 'financial_summary', 'financial_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($targetSummary, 'target_summary', 'target_summary.company_id', '=', 'companies.id')
            ->select([
                'companies.id',
                'companies.name',
                'companies.logo_path',
                'companies.status',
                'companies.created_at',
                DB::raw('COALESCE(portfolio_summary.total_portfolios, 0) as total_portfolios'),
                DB::raw('COALESCE(portfolio_summary.active_portfolios, 0) as active_portfolios'),
                DB::raw('COALESCE(portfolio_summary.inactive_portfolios, 0) as inactive_portfolios'),
                DB::raw('COALESCE(customer_summary.customers_count, 0) as customers_count'),
                DB::raw('COALESCE(financial_summary.total_debt, 0) as total_debt'),
                DB::raw('COALESCE(financial_summary.total_credit, 0) as total_credit'),
                DB::raw("
                    CASE
                        WHEN COALESCE(financial_summary.total_debt, 0) > 0
                        THEN ROUND((COALESCE(financial_summary.total_credit, 0) / financial_summary.total_debt) * 100, 2)
                        ELSE 0
                    END as collection_rate
                "),
                DB::raw('target_summary.target_percentage as target_percentage'),
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'companies.id', 'title' => 'ID', 'visible' => false],
            ['data' => 'name', 'name' => 'companies.name', 'title' => 'اسم الشركة', 'className' => 'text-start', 'width' => '220px'],
            ['data' => 'status', 'name' => 'companies.status', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'portfolios_summary', 'name' => 'total_portfolios', 'title' => 'المحافظ', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
            ['data' => 'customers_count', 'name' => 'customers_count', 'title' => 'العملاء', 'width' => '100px'],
            ['data' => 'total_debt', 'name' => 'total_debt', 'title' => 'إجمالي الديون', 'width' => '140px'],
            ['data' => 'total_credit', 'name' => 'total_credit', 'title' => 'إجمالي التحصيل', 'width' => '140px'],
            ['data' => 'collection_rate', 'name' => 'collection_rate', 'title' => 'نسبة التحصيل', 'orderable' => false, 'searchable' => false, 'width' => '170px'],
            ['data' => 'target_percentage', 'name' => 'target_percentage', 'title' => 'الهدف %', 'width' => '100px'],
            ['data' => 'created_at', 'name' => 'companies.created_at', 'title' => 'تاريخ الإنشاء', 'width' => '130px'],
        ];
    }

    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query)->addIndexColumn();

        $this->addCustomColumns($dataTable);

        return $dataTable->rawColumns($this->rawColumns());
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->editColumn('name', function ($row): string {
                $logo = $row->logo_path ? tasset($row->logo_path) : asset('img/logo.svg');

                return '<div class="d-flex align-items-center gap-2">'
                    . '<img src="' . e($logo) . '" alt="" class="rounded" style="width:30px;height:30px;object-fit:cover">'
                    . '<span class="fw-medium text-body">' . e($row->name) . '</span>'
                    . '</div>';
            })
            ->editColumn('status', function ($row): string {
                $isActive = $row->status === 'active';
                $class = $isActive ? 'bg-label-success' : 'bg-label-danger';
                $label = $isActive ? 'نشط' : 'غير نشط';

                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('portfolios_summary', function ($row): string {
                return '<span class="badge bg-label-info">'
                    . (int) $row->active_portfolios . ' / ' . (int) $row->total_portfolios
                    . '</span>';
            })
            ->editColumn('customers_count', fn($row): string => '<span class="badge bg-label-warning">' . number_format((int) $row->customers_count) . '</span>')
            ->editColumn('total_debt', fn($row): string => $this->formatCurrency((float) $row->total_debt))
            ->editColumn('total_credit', fn($row): string => $this->formatCurrency((float) $row->total_credit))
            ->editColumn('collection_rate', function ($row): string {
                $rate = min(max((float) $row->collection_rate, 0), 100);
                $class = $rate >= 75 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');

                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="progress flex-grow-1" style="height:6px;min-width:70px">'
                    . '<div class="progress-bar bg-' . $class . '" role="progressbar" style="width:' . $rate . '%" aria-valuenow="' . $rate . '" aria-valuemin="0" aria-valuemax="100"></div>'
                    . '</div>'
                    . '<span class="small fw-medium">' . number_format((float) $row->collection_rate, 2) . '%</span>'
                    . '</div>';
            })
            ->editColumn('target_percentage', function ($row): string {
                if ($row->target_percentage === null) {
                    return '<span class="text-muted">بدون هدف</span>';
                }

                return '<span class="badge bg-label-primary">' . number_format((float) $row->target_percentage, 2) . '%</span>';
            })
            ->editColumn('created_at', fn($row): string => Carbon::parse($row->created_at)->format('Y-m-d'));
    }

    protected function rawColumns(): array
    {
        return [
            'name',
            'status',
            'portfolios_summary',
            'customers_count',
            'collection_rate',
            'target_percentage',
        ];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('companies.status', (string) $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('companies.created_at', '>=', (string) $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('companies.created_at', '<=', (string) $request->input('date_to'));
        }

        $search = $request->input('search_query');
        if (! is_string($search) || $search === '') {
            $requestSearch = $request->input('search');
            $search = is_string($requestSearch) ? $requestSearch : null;
        }

        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('companies.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($exists) use ($search): void {
                        $exists->selectRaw('1')
                            ->from('company_authorized_people')
                            ->whereColumn('company_authorized_people.company_id', 'companies.id')
                            ->whereNull('company_authorized_people.deleted_at')
                            ->where(function ($personQuery) use ($search): void {
                                $personQuery->where('company_authorized_people.name', 'like', "%{$search}%")
                                    ->orWhere('company_authorized_people.phone', 'like', "%{$search}%")
                                    ->orWhere('company_authorized_people.email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // ← الإضافة: فلترة بالشركة
        if ($request->filled('company_id')) {
            $query->where('companies.id', (int) $request->input('company_id'));
        }

        // ← الإضافة: فلترة بالمحفظة
        if ($request->filled('portfolio_id')) {
            $query->whereExists(function ($exists) use ($request): void {
                $exists->selectRaw('1')
                    ->from('portfolios')
                    ->whereColumn('portfolios.company_id', 'companies.id')
                    ->whereNull('portfolios.deleted_at')
                    ->where('portfolios.id', (int) $request->input('portfolio_id'));
            });
        }

        return $query;
    }

    protected function buttons(): array
    {
        return [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => [
                    ['extend' => 'copy', 'text' => 'نسخ'],
                    [
                        'text' => 'تصدير Excel',
                        'className' => 'btn-export-excel',
                        'action' => "function() { exportCompaniesToExcel(); }",
                    ],
                ],
            ],
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 2) . ' ريال';
    }
}
