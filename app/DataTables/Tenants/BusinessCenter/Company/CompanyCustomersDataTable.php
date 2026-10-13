<?php

namespace App\DataTables\Tenants\BusinessCenter\Company;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Support\Facades\Log;

class CompanyCustomersDataTable extends AbstractDataTable
{
    private $companyId;
    // private $route = "reports.companies";

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
        parent::__construct();
    }

    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;

        return $this;
    }

    protected function getTableId()
    {
        return 'company-customers-table';
    }

    protected function resource()
    {

        return Customer::query()

            // ->join('customers', 'collections.customer_id', '=', 'customers.id')
            // ->with(['paymentMethod:id,name'])
            ->where('company_id', $this->companyId);
    }

    // protected function getBuilderParameters(): array
    // {
    //     return [
    //         'ajax' => [
    //             'url' => url()->current() . '?table=customers',
    //             'type' => 'GET',
    //         ],
    //     ];
    // }
    protected function getColumns(): array
    {
        return [
            // ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => 'اسم العميل',
                'className' => 'text-center',
            ],
            // ['data' => 'identity_number', 'name' => 'identity_number', 'title' => 'رقم الهوية'],
            // ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الهاتف'],
            // ['data' => 'portfolio', 'name' => 'portfolio', 'title' => 'المحفظة', 'orderable' => false, 'searchable' => false],
            // ['data' => 'customer_type', 'name' => 'customer_type', 'title' => 'نوع العميل'],
            // ['data' => 'contract_date', 'name' => 'contract_date', 'title' => 'تاريخ التعاقد'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn ($row): string => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->editColumn('name', function ($row) {
                Log::info('Customer Name: '.$row->name);

                return $row->name;
            });

        // ->editColumn('customer_identity_number', function ($customer) {
        //     return '<code class="bg-light px-2 py-1 rounded">' .
        //         e($customer->customer_identity_number ?? '—') .
        //         '</code>';
        // });

        //     ->editColumn('phone', function ($customer) {
        //         return '<span class="text-muted">' . e($customer->phone ?? '—') . '</span>';
        //     })

        //     ->addColumn('portfolio', function ($customer) {
        //         if ($customer->portfolio) {
        //             return '<span class="badge badge-custom bg-label-warning">' .
        //                 e($customer->portfolio->name) .
        //                 '</span>';
        //         }
        //         return '<span class="text-muted">—</span>';
        //     })

        //     ->editColumn('customer_type', function ($customer) {
        //         return $this->customerTypeBadge($customer);
        //     })

        //     ->editColumn('contract_date', function ($customer) {
        //         if ($customer->contract_date) {
        //             return '<span class="text-muted">' .
        //                 \Carbon\Carbon::parse($customer->contract_date)->format('Y-m-d') .
        //                 '</span>';
        //         }
        //         return '<span class="text-muted">—</span>';
        //     });

        // ->filterColumn('name', function ($query, $keyword) {
        //     $query->where('name', 'like', "%{$keyword}%");
        // })

        // ->filterColumn('identity_number', function ($query, $keyword) {
        //     $query->where('identity_number', 'like', "%{$keyword}%");
        // })

        // ->filterColumn('phone', function ($query, $keyword) {
        //     $query->where('phone', 'like', "%{$keyword}%");
        // })

        // ->orderColumn('name', 'name $1')
        // ->orderColumn('identity_number', 'identity_number $1')
        // ->orderColumn('phone', 'phone $1')
        // ->orderColumn('customer_type', 'customer_type $1')
        // ->orderColumn('contract_date', 'contract_date $1');
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'name',
            // 'identity_number',
        ];
    }

    // protected function applyCustomFilters($query)
    // {
    //     $request = request();

    //     if ($request->filled('customer_type')) {
    //         $query->where('customer_type', $request->customer_type);
    //     }

    //     if ($request->filled('portfolio_id')) {
    //         $query->where('portfolio_id', $request->portfolio_id);
    //     }

    //     if ($request->filled('date_from') && $request->filled('date_to')) {
    //         $query->whereBetween('contract_date', [$request->date_from, $request->date_to]);
    //     }

    //     return $query;
    // }

    // protected function getCustomOrder(): array
    // {
    //     return [4, 'desc']; // contract_date (index 5 after removing DT_RowIndex)
    // }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'title' => 'عملاء الشركة'],
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-secondary dropdown-toggle mx-3',
            'text' => '<i class="ti ti-download me-1"></i>تصدير',
            'buttons' => $buttons,
        ];
    }

    private function customerTypeBadge($customer): string
    {
        $typeClasses = [
            'individual' => 'info',
            'company' => 'primary',
        ];

        $typeLabels = [
            'individual' => 'فردي',
            'company' => 'شركة',
        ];

        $typeIcons = [
            'individual' => 'user',
            'company' => 'building',
        ];

        $type = $typeClasses[$customer->customer_type] ?? 'secondary';
        $label = $typeLabels[$customer->customer_type] ?? $customer->customer_type;
        $icon = $typeIcons[$customer->customer_type] ?? 'help';

        return sprintf(
            '<span class="badge bg-label-%s"><i class="ti ti-%s me-1"></i>%s</span>',
            $type,
            $icon,
            $label
        );
    }
}
