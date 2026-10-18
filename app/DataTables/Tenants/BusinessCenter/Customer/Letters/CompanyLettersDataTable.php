<?php

namespace App\DataTables\Tenants\BusinessCenter\Customer\Letters;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;

class CompanyLettersDataTable extends AbstractDataTable
{
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    private $route = 'company-letters';

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
        return 'company-letters-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return CompanyLetter::with([
            'customer:id,name,account_number,company_id,portfolio_id',
            'customer.company:id,name',
            'customer.portfolio:id,name',
            'customerFollow:id,contact_result_id,created_by',
            'customerFollow.contactResult:id,name',
            'customerFollow.createdBy:id,name',
        ])->select('company_letters.*');
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'customer_name', 'name' => 'customer.name', 'title' => 'العميل'],
            ['data' => 'account_number', 'name' => 'customer.account_number', 'title' => 'رقم الحساب'],
            ['data' => 'company_name', 'name' => 'customer.company.name', 'title' => 'الشركة'],
            ['data' => 'portfolio_name', 'name' => 'customer.portfolio.name', 'title' => 'المحفظة'],
            ['data' => 'contact_result', 'name' => 'customerFollow.contactResult.name', 'title' => 'نتيجة الاتصال'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'user_name', 'name' => 'customerFollow.createdBy.name', 'title' => 'المستخدم'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Custom Columns
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('customer_name', function ($row) {
                return '<span class="fw-bold text-dark">'.e($row->customer->name ?? 'غير محدد').'</span>';
            })
            ->editColumn('account_number', function ($row) {
                return '<span class="badge bg-secondary">'.e($row->customer->account_number ?? '-').'</span>';
            })
            ->editColumn('company_name', function ($row) {
                return '<span class="text-muted">'.e($row->customer->company->name ?? 'غير محدد').'</span>';
            })
            ->editColumn('portfolio_name', function ($row) {
                return '<span class="text-muted">'.e($row->customer->portfolio->name ?? 'غير محدد').'</span>';
            })
            ->editColumn('contact_result', function ($row) {
                return '<span class="badge bg-label-info">'.
                       e($row->customerFollow->contactResult->name ?? 'غير محدد').
                       '</span>';
            })
            ->editColumn('created_at', function ($row) {
                return '<span class="text-muted">'.$row->created_at->format('Y-m-d H:i').'</span>';
            })
            ->editColumn('status', function ($row) {
                return '<span class="badge '.$row->status_class.'">'.
                       $row->status_label.
                       '</span>';
            })
            ->editColumn('user_name', function ($row) {
                return '<span class="text-dark">'.
                       e($row->customerFollow->createdBy->name ?? 'غير محدد').
                       '</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.business_center.customers.letters.action', [
                    'id' => $row->id,
                    'route' => $this->route,
                ])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'customer_name',
            'account_number',
            'company_name',
            'portfolio_name',
            'contact_result',
            'created_at',
            'status',
            'user_name',
            'actions',
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('portfolio_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('portfolio_id', $request->portfolio_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
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
            $this->getExportButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export Button
    |--------------------------------------------------------------------------
    */
    private function getExportButton(): array
    {
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export dropdown-toggle me-2',
            'text' => 'الإجراءات',
            'buttons' => [
                [
                    'extend' => 'excel',
                    'text' => 'إكسل',
                    'className' => 'dropdown-item',
                ],
                [
                    'extend' => 'copy',
                    'text' => 'نسخ',
                    'className' => 'dropdown-item',
                ],
            ],
        ];
    }
}
