<?php

namespace App\DataTables\Tenants\Reports\Customer;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Carbon\Carbon;

class CustomerReportsDataTable extends AbstractDataTable
{
    private $route = "reports.customers";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    | Build the DataTable response and mark raw columns.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();

        $datatable->filterColumn('debt_amount', function ($query, $keyword) {
            if (is_numeric($keyword)) {
                $query->havingRaw('ABS(debt_amount - ?) < 0.01', [(float) $keyword]);
            }
        });

        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Table Id
    |--------------------------------------------------------------------------
    | Provide the unique HTML id for the table element.
    */
    protected function getTableId()
    {
        return "customer-reports-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return Customer::addSelect([
            'customers.*',
            'debt_amount' => CustomerTransaction::selectRaw('
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
             ')->whereColumn('customer_id', 'customers.id')
        ])
            ->with([
                'company:id,name',
                'portfolio:id,name',
                'relationshipManager:id,name',
                'customerStatus:id,name',
                'lastContactResult:id,name'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    | Define the table columns structure.
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'customer_info', 'name' => 'name', 'title' => 'معلومات العميل', 'width' => '250px', 'className' => 'text-start'],
            ['data' => 'customer_type', 'name' => 'customer_type', 'title' => 'النوع', 'width' => '80px'],
            ['data' => 'contact_info', 'name' => 'phone', 'title' => 'بيانات الاتصال', 'width' => '150px'],
            ['data' => 'company_info', 'name' => 'company_id', 'title' => 'الشركة', 'width' => '150px', 'className' => 'text-start'],
            ['data' => 'portfolio_info', 'name' => 'portfolio_id', 'title' => 'المحفظة', 'width' => '150px', 'className' => 'text-start'],
            ['data' => 'debt_amount', 'name' => 'debt_amount', 'title' => 'المديونية', 'width' => '120px', 'className' => 'text-center'],
            ['data' => 'manager_info', 'name' => 'relationship_manager_id', 'title' => 'المسؤول', 'width' => '150px', 'className' => 'text-start'],
            ['data' => 'contact_result', 'name' => 'last_contact_result_id', 'title' => 'نتيجة الاتصال', 'width' => '150px'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'width' => '120px'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Columns
    |--------------------------------------------------------------------------
    | Add/format custom columns.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('customer_info', function ($row) {
                return '<div class="customer-info">
                    <div>' . e($row->name) . '</div>
                </div>';
            })

            ->editColumn('customer_type', function ($row) {
                $typeText = $row->customer_type === 'individual' ? 'فردي' : 'شركة';
                return '<div>' . $typeText . '</div>';
            })

            ->editColumn('contact_info', function ($row) {
                $phone = $row->phone ?: 'لا يوجد';
                return '<div class="contact-info">
                <div>' . e($phone) . '</div>
                   </div>';
            })

            ->editColumn('company_info', function ($row) {
                $companyName = $row->company->name ?? 'غير محدد';
                return '<div class="company-info">
                    <div>' . e($companyName) . '</div>
                </div>';
            })

            ->editColumn('portfolio_info', function ($row) {
                $portfolioName = $row->portfolio->name ?? 'غير محدد';
                return '<div class="portfolio-info">
                    <div>' . e($portfolioName) . '</div>
                </div>';
            })

            ->editColumn('debt_amount', function ($row) {
                $debtAmount = $row->debt_amount ?? 0;
                return '<div>' . number_format($debtAmount, 2) . ' ريال</div>';
            })

            ->editColumn('manager_info', function ($row) {
                $managerName = $row->relationshipManager->name ?? 'غير محدد';
                return '<div class="manager-info">
                    <div>' . e($managerName) . '</div>
                </div>';
            })

            ->editColumn('contact_result', function ($row) {
                $resultName = $row->lastContactResult->name ?? 'غير  محدد';
                return '<div>' . e($resultName) . '</div>';
            })

            ->editColumn('created_at', function ($row) {
                return '<div>' . Carbon::parse($row->created_at)->format('Y-m-d') . '</div>';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    | Specify columns that contain raw HTML.
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'customer_info', 'customer_type', 'contact_info', 'company_info', 'portfolio_info', 'debt_amount', 'manager_info', 'contact_result', 'created_at'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    | Apply custom filters coming from the UI.
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('date_from')) {
            $query->whereDate('customers.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('customers.created_at', '<=', $request->date_to);
        }

        if ($request->filled('customer_status_id')) {
            $query->where('customer_status_id', $request->customer_status_id);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('portfolio_id')) {
            $query->where('portfolio_id', $request->portfolio_id);
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        if ($request->has('relationship_manager_id')) {
            $managers = $request->input('relationship_manager_id', []);

            if (is_string($managers)) {
                $managers = [$managers];
            }

            $managers = array_filter($managers, function ($value) {
                return !empty($value);
            });

            if (!empty($managers)) {
                $query->whereIn('relationship_manager_id', $managers);
            }
        }

        if ($request->has('contact_result_id')) {
            $results = $request->input('contact_result_id', []);

            if (is_string($results)) {
                $results = [$results];
            }

            $results = array_filter($results, function ($value) {
                return !empty($value);
            });

            if (!empty($results)) {
                $query->whereIn('last_contact_result_id', $results);
            }
        }

        if ($request->filled('debt_from')) {
            $query->havingRaw('debt_amount >= ?', [(float) $request->debt_from]);
        }
        if ($request->filled('debt_to')) {
            $query->havingRaw('debt_amount <= ?', [(float) $request->debt_to]);
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customers.name', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.identity_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.phone', 'like', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    | Define DataTable top buttons.
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
    | Build the 'Actions' dropdown content.
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            [
                'text' => 'تصدير Excel',
                'className' => 'btn-export-excel',
                'action' => "function() { exportFilteredToExcel(); }",
            ]
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
    | Define the primary button (if needed).
    */
    private function getAddButton(): array
    {
        return [];
    }
}
