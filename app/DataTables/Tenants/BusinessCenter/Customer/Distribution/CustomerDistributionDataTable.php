<?php

namespace App\DataTables\Tenants\BusinessCenter\Customer\Distribution;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerDistributionDataTable extends AbstractDataTable
{
    private $route = 'customers.distribution';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return 'distributions-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return CustomerDistribution::query()
            ->with(['createdBy:id,name'])
            ->select([
                'customer_distributions.batch_id',
                'customer_distributions.distribution_method',
                'customer_distributions.created_by',
                'customer_distributions.created_at',
                DB::raw('COUNT(*) as collectors_count'),
                DB::raw('SUM(customer_distributions.customers_count) as total_customers'),
                DB::raw('SUM(customer_distributions.total_debt) as total_debt_sum'),
                DB::raw('MIN(customer_distributions.id) as first_id'),
            ])
            ->groupBy(['customer_distributions.batch_id', 'customer_distributions.distribution_method', 'customer_distributions.created_by', 'customer_distributions.created_at'])
            ->orderBy('customer_distributions.created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#', 'width' => '30px'],
            ['data' => 'batch_id', 'name' => 'batch_id', 'title' => 'معرف العملية', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all" class="form-check-input">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
            ],
            ['data' => 'distribution_date_formatted', 'name' => 'created_at', 'title' => 'تاريخ التوزيع', 'width' => '120px', 'searchable' => true],
            ['data' => 'distribution_method_badge', 'name' => 'distribution_method', 'title' => 'نوع التوزيع', 'width' => '140px', 'searchable' => true],
            ['data' => 'collectors_count_badge', 'name' => 'collectors_count', 'title' => 'عدد المحصلين', 'width' => '100px', 'searchable' => false],
            ['data' => 'total_customers_badge', 'name' => 'total_customers', 'title' => 'إجمالي العملاء', 'width' => '100px', 'searchable' => false],
            ['data' => 'total_debt_formatted', 'name' => 'total_debt_sum', 'title' => 'إجمالي المديونية', 'width' => '140px', 'searchable' => false],
            ['data' => 'created_by_name', 'name' => 'createdBy.name', 'title' => 'تم بواسطة', 'width' => '100px', 'searchable' => true],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Custom Columns
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Add Custom Columns
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $distributionMethods = [
            'balanced_amount' => ['label' => 'توزيع متوازن (مبلغ)', 'class' => 'primary'],
            'balanced_count' => ['label' => 'توزيع متوازن (عدد)', 'class' => 'info'],
            'hybrid_balanced' => ['label' => 'توزيع مختلط', 'class' => 'success'],
        ];

        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->batch_id.'">')

            ->addColumn('distribution_date_formatted', function ($row) {
                $date = Carbon::parse($row->created_at);
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none" style="color: inherit;">'.
                    '<span>'.$date->format('Y-m-d').'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->addColumn('distribution_method_badge', function ($row) use ($distributionMethods) {
                $method = $distributionMethods[$row->distribution_method] ?? ['label' => 'غير محدد', 'class' => 'secondary'];
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none">'.
                    '<span class="badge bg-label-'.$method['class'].'">'.e($method['label']).'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->addColumn('collectors_count_badge', function ($row) {
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none" style="color: inherit;">'.
                    '<span>'.number_format($row->collectors_count).'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->addColumn('total_customers_badge', function ($row) {
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none" style="color: inherit;">'.
                    '<span>'.number_format($row->total_customers).'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->addColumn('total_debt_formatted', function ($row) {
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none">'.
                    '<span class="text-primary">'.number_format($row->total_debt_sum, 2).'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->addColumn('created_by_name', function ($row) {
                $createdBy = $row->createdBy->name ?? 'غير محدد';
                $showUrl = tenant_route_name('customers.distribution.show', $row->batch_id);

                return '<div class="text-center">'.
                    '<a href="'.$showUrl.'" class="text-decoration-none" style="color: inherit;">'.
                    '<span class="text-truncate">'.e($createdBy).'</span>'.
                    '</a>'.
                    '</div>';
            })

            ->orderColumn('created_at', fn ($query, $order) => $query->orderBy('created_at', $order))
            ->orderColumn('distribution_method', fn ($query, $order) => $query->orderBy('distribution_method', $order))
            ->orderColumn('total_debt_sum', function ($query, $order) {
                return $query->orderByRaw('SUM(total_debt) '.$order);
            })
            ->orderColumn('collectors_count', function ($query, $order) {
                return $query->orderByRaw('COUNT(*) '.$order);
            })
            ->orderColumn('total_customers', function ($query, $order) {
                return $query->orderByRaw('SUM(customers_count) '.$order);
            })

            // فلاتر البحث
            ->filterColumn('distribution_method_badge', function ($query, $keyword) {
                $query->where('distribution_method', 'like', "%{$keyword}%");
            })
            ->filterColumn('created_by_name', function ($query, $keyword) {
                $query->whereHas('createdBy', function ($createdByQuery) use ($keyword) {
                    $createdByQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('distribution_date_formatted', function ($query, $keyword) {
                $query->whereDate('created_at', 'like', "%{$keyword}%");
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Specify Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'distribution_date_formatted',
            'distribution_method_badge',
            'collectors_count_badge',
            'total_customers_badge',
            'total_debt_formatted',
            'created_by_name',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('distribution_method')) {
            $query->where('customer_distributions.distribution_method', $request->distribution_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('customer_distributions.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('customer_distributions.created_at', '<=', $request->date_to);
        }

        if ($request->filled('created_by')) {
            $query->where('customer_distributions.created_by', $request->created_by);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Define DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Actions Dropdown Button
    |--------------------------------------------------------------------------
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            [
                'extend' => 'copy',
                'text' => 'نسخ',
            ],
            [
                'extend' => 'excel',
                'text' => 'إكسل',
            ],
        ];

        if (Auth::user()->can('customer_distributions.delete')) {
            $buttons[] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => 'function() { handleMassDelete(); }',
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Add New Distribution Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        if (! auth()->user()?->can('customer_distributions.create')) {
            return [];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> توزيع جديد',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }
}
