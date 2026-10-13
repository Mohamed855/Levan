<?php

namespace App\DataTables\Tenants\Reports\CustomerFollow;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use Carbon\Carbon;

class CustomerFollowReportsDataTable extends AbstractDataTable
{
    private $route = 'reports.customer_follows';

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
        return 'customer-follows-reports-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return CustomerFollow::query()
            ->join('customers', 'customer_follows.customer_id', '=', 'customers.id')
            ->leftJoin('companies', 'customers.company_id', '=', 'companies.id')
            ->leftJoin('portfolios', 'customers.portfolio_id', '=', 'portfolios.id')
            ->leftJoin('settings_contact_methods', 'customer_follows.contact_method_id', '=', 'settings_contact_methods.id')
            ->leftJoin('settings_customer_status', 'customer_follows.customer_status_id', '=', 'settings_customer_status.id')
            ->leftJoin('contact_results', 'customer_follows.contact_result_id', '=', 'contact_results.id')
            ->leftJoin('users', 'customer_follows.created_by', '=', 'users.id')
            ->whereNull('customers.deleted_at')
            ->select(
                'customer_follows.id',
                'customer_follows.created_at',
                'customers.name as customer_name',
                'companies.name as company_name',
                'portfolios.name as portfolio_name',
                'settings_contact_methods.name as contact_method_name',
                'settings_customer_status.name as customer_status_name',
                'contact_results.name as contact_result_name',
                'users.name as user_name'
            );
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
            ['data' => 'contact_method', 'name' => 'contact_method_id', 'title' => 'طريقة الاتصال', 'width' => '120px'],
            ['data' => 'contact_result', 'name' => 'contact_result_id', 'title' => 'نتيجة الاتصال', 'width' => '150px'],
            ['data' => 'customer_status', 'name' => 'customer_status_id', 'title' => 'حالة العميل', 'width' => '150px'],
            // ['data' => 'next_contact_datetime', 'name' => 'next_contact_datetime', 'title' => 'الاتصال القادم', 'width' => '150px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'أنشأ بواسطة', 'width' => '150px', 'className' => 'text-start'],
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
                return '<div class="customer-info">
                <div class="fw-semibold">'.e($row->customer_name ?? 'غير محدد').'</div>
                <small class="text-muted">الشركة: '.e($row->company_name ?? 'غير محدد').'</small><br>
                <small class="text-muted">المحفظة: '.e($row->portfolio_name ?? 'غير محدد').'</small>
            </div>';
            })

            ->editColumn('contact_method', function ($row) {
                return '<div>'.e($row->contact_method_name ?? 'غير محدد').'</div>';
            })

            ->editColumn('contact_result', function ($row) {
                return '<div>'.e($row->contact_result_name ?? 'غير محدد').'</div>';
            })

            ->editColumn('customer_status', function ($row) {
                return '<div>'.e($row->customer_status_name ?? 'غير محدد').'</div>';
            })

            ->editColumn('created_by', function ($row) {
                return '<div class="user-info">
                <div>'.e($row->user_name ?? 'غير محدد').'</div>
            </div>';
            })

            ->editColumn('created_at', function ($row) {
                return '<div>'.Carbon::parse($row->created_at)->format('Y-m-d').'</div>';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'customer_info', 'contact_method', 'contact_result', 'customer_status', 'next_contact_datetime', 'created_by', 'created_at'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        // فلتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('customer_follows.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('customer_follows.created_at', '<=', $request->date_to);
        }

        // فلتر تاريخ الاتصال القادم
        if ($request->filled('next_contact_from')) {
            $query->whereDate('customer_follows.next_contact_datetime', '>=', $request->next_contact_from);
        }
        if ($request->filled('next_contact_to')) {
            $query->whereDate('customer_follows.next_contact_datetime', '<=', $request->next_contact_to);
        }

        // فلتر العميل
        if ($request->filled('customer_id')) {
            $query->where('customer_follows.customer_id', $request->customer_id);
        }

        if ($request->filled('company_id')) {
            $query->where('customers.company_id', $request->company_id);
        }

        // إضافة فلتر المحفظة
        if ($request->filled('portfolio_id')) {
            $query->where('customers.portfolio_id', $request->portfolio_id);
        }

        // فلتر طريقة الاتصال
        if ($request->filled('contact_method_id')) {
            $query->where('customer_follows.contact_method_id', $request->contact_method_id);
        }

        // فلتر حالة العميل - تصحيح المشكلة
        if ($request->filled('customer_status_id')) {
            $query->where('customer_follows.customer_status_id', $request->customer_status_id);
        }

        // فلتر نتيجة الاتصال
        if ($request->filled('contact_result_id')) {
            $query->where('customer_follows.contact_result_id', $request->contact_result_id);
        }

        // فلتر المستخدم
        if ($request->filled('created_by')) {
            $query->where('customer_follows.created_by', $request->created_by);
        }

        // فلتر وجود متابعات قادمة
        if ($request->filled('has_upcoming')) {
            if ($request->has_upcoming == '1') {
                $query->whereNotNull('customer_follows.next_contact_datetime')
                    ->where('customer_follows.next_contact_datetime', '>', now());
            } else {
                $query->whereNull('customer_follows.next_contact_datetime')
                    ->orWhere('customer_follows.next_contact_datetime', '<=', now());
            }
        }

        // فلتر البحث النصي - تحسين البحث
        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_follows.contact_location', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_follows.contacted_person_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_follows.notes', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('account_number', 'like', "%{$searchTerm}%")
                            ->orWhere('identity_number', 'like', "%{$searchTerm}%");
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
        return [
            [
                'text' => 'تصدير  <i class="fa-solid fa-file-excel mx-2"></i>',
                'className' => 'btn btn-primary btn-export-excel',
                'action' => 'function() { exportFilteredToExcel(); }',
            ],
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
