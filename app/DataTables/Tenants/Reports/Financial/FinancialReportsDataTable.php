<?php

namespace App\DataTables\Tenants\Reports\Financial;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Carbon\Carbon;

class FinancialReportsDataTable extends AbstractDataTable
{
    private $route = 'reports.financial';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
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
    | Table Id
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return 'financial-reports-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return Collection::query()
            ->with([
                'customer:id,name,account_number',
                'paymentMethod:id,name',
                'addedBy:id,name',
            ])
            ->whereHas('customer')
            ->select('collections.*');
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'customer_info', 'name' => 'customer_id', 'title' => 'العميل', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'customer_account', 'name' => 'customer.account_number', 'title' => 'رقم الحساب', 'width' => '120px', 'className' => 'text-start'], // إضافة هذا العمود
            ['data' => 'amount_info', 'name' => 'collected_amount', 'title' => 'المبلغ', 'width' => '150px'],
            ['data' => 'payment_method', 'name' => 'payment_method_id', 'title' => 'طريقة الدفع', 'width' => '150px'],
            ['data' => 'status_info', 'name' => 'status', 'title' => 'الحالة', 'width' => '120px'],
            ['data' => 'collection_date', 'name' => 'collection_date', 'title' => 'تاريخ التحصيل', 'width' => '120px'],
            ['data' => 'created_by', 'name' => 'added_by', 'title' => 'أنشأ بواسطة', 'width' => '150px', 'className' => 'text-start'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'width' => '120px'],
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
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('customer_info', function ($row) {
                $customerName = $row->customer->name ?? 'غير محدد';

                return '<div class="customer-info">
                <div><strong>'.e($customerName).'</strong></div>
            </div>';
            })

            ->addColumn('customer_account', function ($row) {
                $customerAccount = $row->customer->account_number ?? 'غير محدد';

                return '<div class="customer-account">
                <div>'.e($customerAccount).'</div>
            </div>';
            })

            ->editColumn('amount_info', function ($row) {
                return '<span class="fw-bold text-primary">'.
                    number_format($row->collected_amount, 2).' ريال</span>';
            })

            ->editColumn('payment_method', function ($row) {
                return '<div>'.e($row->paymentMethod->name ?? 'غير محدد').'</div>';
            })

            ->editColumn('status_info', function ($row) {
                $statusClass = match ($row->status) {
                    'approved' => 'bg-label-success',
                    'pending' => 'bg-label-warning',
                    'rejected' => 'bg-label-danger',
                    default => 'bg-secondary'
                };
                $statusLabel = match ($row->status) {
                    'approved' => 'معتمد',
                    'pending' => 'معلق',
                    'rejected' => 'مرفوض',
                    default => $row->status
                };

                return '<span class="badge '.$statusClass.'">'.$statusLabel.'</span>';
            })

            ->editColumn('collection_date', function ($row) {
                return '<div>'.Carbon::parse($row->collection_date)->format('Y-m-d').'</div>';
            })

            ->editColumn('created_by', function ($row) {
                $userName = $row->addedBy->name ?? 'غير محدد';

                return '<div class="user-info">
                <div>'.e($userName).'</div>
            </div>';
            })

            ->editColumn('created_at', function ($row) {
                return '<div>'.Carbon::parse($row->created_at)->format('Y-m-d H:i').'</div>';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'customer_info', 'customer_account', 'amount_info', 'payment_method', 'status_info', 'collection_date', 'created_by', 'created_at'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('company_id')) {
            $query->whereHas('customer', function ($customerQuery) use ($request) {
                $customerQuery->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('portfolio_id')) {
            $query->whereHas('customer', function ($customerQuery) use ($request) {
                $customerQuery->where('portfolio_id', $request->portfolio_id);
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('collections.collection_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('collections.collection_date', '<=', $request->date_to);
        }

        if ($request->filled('created_from')) {
            $query->whereDate('collections.created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('collections.created_at', '<=', $request->created_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('collections.status', $request->status);
        }

        if ($request->filled('created_by')) {
            $query->where('added_by', $request->created_by);
        }

        if ($request->filled('amount_from')) {
            $query->where('collected_amount', '>=', $request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('collected_amount', '<=', $request->amount_to);
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('collections.receipt_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

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
            $this->getAddButton(),
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
            // ,
            // [
            //     'text' => 'تصدير PDF',
            //     'className' => 'btn btn-default btn-export-pdf',
            //     'action' => "function() { exportCustomReportToPDF(); }",
            // ]
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        return [];
    }
}
