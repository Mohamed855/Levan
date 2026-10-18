<?php

namespace App\DataTables\Tenants\BusinessCenter\Customer;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\BusinessCenter\Customer\CustomerService;

class CustomersDataTable extends AbstractDataTable
{
    /**
     * @var CustomerService
     */
    protected $customerService;

    private $route = 'customers';

    private $viewType;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Injects the CustomerService for data retrieval.
    */
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;

        $routeName = optional(request()->route())->getName() ?? '';
        $this->viewType = str_ends_with($routeName, 'customers.my-customers') ? 'my' : 'all';
    }

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    | This is the core method for processing and formatting the data.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        $datatable = $this->applyFilters($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters - مُصلح ومُحدث للبحث العام
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر المخصصة + البحث العام من DataTable
    */
    private function applyFilters($datatable)
    {
        $datatable->filter(function ($query) {
            if (request()->filled('customer_status_id')) {
                $query->where('customer_status_id', request('customer_status_id'));
            }

            if (request()->filled('last_contact_result_id')) {
                $query->where('last_contact_result_id', request('last_contact_result_id'));
            }

            if (request()->filled('customer_type')) {
                $query->where('customer_type', request('customer_type'));
            }

            if (request()->filled('company_id')) {
                $query->where('company_id', request('company_id'));
            }

            if (request()->filled('portfolio_id')) {
                $query->where('portfolio_id', request('portfolio_id'));
            }

            if (request()->filled('nationality')) {
                $query->where('nationality', request('nationality'));
            }

            // ✅ التعديل الجديد - دعم التحديد المتعدد
            if (request()->filled('relationship_manager_id')) {
                $managerIds = request('relationship_manager_id');

                // إذا كان array (تحديد متعدد)
                if (is_array($managerIds) && count($managerIds) > 0) {
                    $query->whereIn('relationship_manager_id', $managerIds);
                }
                // إذا كان string واحد
                elseif (! is_array($managerIds) && ! empty($managerIds)) {
                    $query->where('relationship_manager_id', $managerIds);
                }
            }

            // فلتر حالة الإرسال
            if (request()->filled('sms_status')) {
                if (request('sms_status') === 'sent') {
                    $query->whereNotNull('last_sms_at');
                } elseif (request('sms_status') === 'not_sent') {
                    $query->whereNull('last_sms_at');
                }
            }

            // فلتر تاريخ الإرسال - من
            if (request()->filled('sms_date_from')) {
                $query->where('last_sms_at', '>=', request('sms_date_from').' 00:00:00');
            }

            // فلتر تاريخ الإرسال - إلى
            if (request()->filled('sms_date_to')) {
                $query->where('last_sms_at', '<=', request('sms_date_to').' 23:59:59');
            }

            $globalSearch = request()->input('search.value');
            if (! empty($globalSearch)) {
                $cleanSearch = trim(preg_replace('/\s+/', ' ', $globalSearch));
                $searchWords = explode(' ', $cleanSearch);

                $query->where(function ($q) use ($cleanSearch, $searchWords) {
                    $q->where('name', 'like', "%{$cleanSearch}%")
                        ->orWhere('identity_number', 'like', "%{$cleanSearch}%")
                        ->orWhere('phone', 'like', "%{$cleanSearch}%")
                        ->orWhere('account_number', 'like', "%{$cleanSearch}%");

                    if (count($searchWords) > 1) {
                        $q->orWhere(function ($subQuery) use ($searchWords) {
                            foreach ($searchWords as $word) {
                                if (strlen($word) >= 2) {
                                    $subQuery->where('name', 'like', "%{$word}%");
                                }
                            }
                        });
                    }

                    $q->orWhereHas('phones', function ($phoneQuery) use ($cleanSearch) {
                        $phoneQuery->where('phone', 'like', "%{$cleanSearch}%");
                    })
                        ->orWhereHas('company', function ($companyQuery) use ($cleanSearch) {
                            $companyQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('portfolio', function ($portfolioQuery) use ($cleanSearch) {
                            $portfolioQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('relationshipManager', function ($managerQuery) use ($cleanSearch) {
                            $managerQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('customerStatus', function ($statusQuery) use ($cleanSearch) {
                            $statusQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('dynamicValues', function ($dynQuery) use ($cleanSearch) {
                            $dynQuery->where('value', 'like', "%{$cleanSearch}%");
                        });
                });
            }
        });

        return $datatable;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Query Resource
    |--------------------------------------------------------------------------
    | Defines the base query for the DataTable.
    */
    protected function resource()
    {
        return $this->customerService->getCustomersQuery($this->viewType);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId(): string
    {
        return 'customers-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    | Defines the structure and headers of the table.
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#', 'className' => 'text-center'],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'createdCell' => "function(td) { $(td).css('padding-left', '38px'); }",
            ],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العميل', 'searchable' => true],
            ['data' => 'account_number', 'name' => 'account_number', 'title' => 'رقم الحساب', 'searchable' => true], // عمود جديد
            ['data' => 'identity_info', 'name' => 'identity_info', 'title' => 'رقم الهوية/رقم الجواز', 'orderable' => false, 'searchable' => true],
            ['data' => 'company_portfolio', 'name' => 'company_portfolio', 'title' => 'الشركة/المحفظة', 'orderable' => false, 'searchable' => true],
            ['data' => 'contact_info', 'name' => 'contact_info', 'title' => 'بيانات الاتصال', 'orderable' => false, 'searchable' => true],
            ['data' => 'customer_type_label', 'name' => 'customer_type', 'title' => 'نوع العميل', 'searchable' => true],
            ['data' => 'status', 'name' => 'customer_status_id', 'title' => 'الحالة', 'searchable' => true],
            ['data' => 'relationship_manager', 'name' => 'relationship_manager', 'title' => 'مسؤول العلاقة', 'orderable' => false, 'searchable' => true],
            ['data' => 'last_contact_result', 'name' => 'last_contact_result', 'title' => 'نتيجة الاتصال', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at_formatted', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'searchable' => false],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'status_color', 'name' => 'status_color', 'visible' => false, 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Checkbox Header
    |--------------------------------------------------------------------------
    */
    private function getCheckboxHeader(): string
    {
        return '<div class="d-flex justify-content-center h-100 w-100" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" tabindex="0" id="selectAllDropdownCell">
                <input type="checkbox" id="select-all" disabled style="pointer-events: none;">
                <i class="ti ti-chevron-down ms-3"></i>
            </div>
            <ul class="dropdown-menu p-2" aria-labelledby="selectAllDropdownCell" style="min-width: 180px;">
                <li>
                    <a href="#" class="dropdown-item d-flex align-items-center" id="select-page">
                        <input type="checkbox" class="row-checkbox me-2" id="selectPageCheckbox" disabled>
                        <span>الصفحة</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="dropdown-item d-flex align-items-center" id="select-all-items">
                        <input type="checkbox" class="row-checkbox me-2" id="selectAllCheckbox" disabled>
                        <span>الكل</span>
                    </a>
                </li>
            </ul>';
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    | This is where we handle the display logic for each column.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('name', function (Customer $customer) {
                $showUrl = tenant_route_name('customers.show', $customer->id);

                return '<div>'.
                    '<a href="'.$showUrl.'" class=" text-decoration-none" style="color: #000;">'.e($customer->name).'</a>'.
                    '<div class="small">'.$customer->customer_type_label.'</div>'.
                    '</div>';
            })
            ->addColumn('account_number', function (Customer $customer) {
                return '<span class="text-muted">'.($customer->account_number ?? 'غير محدد').'</span>';
            })
            ->addColumn('identity_info', function (Customer $customer) {
                return '<div>'.
                    '<span class="">'.e($customer->identity_number).'</span>'.
                    '</div>';
            })
            ->addColumn('company_portfolio', function (Customer $customer) {
                $companyHtml = '';

                if ($customer->company) {
                    $img = tasset($customer->company->logo_path ?? null);

                    $companyHtml = '<div class="d-flex align-items-center" style="min-height: 1.5rem;">'.
                        '<img src="'.$img.'" alt="'.e($customer->company->name).'" class="rounded me-2" style="width:28px;height:28px;object-fit:cover" />'.
                        '<span class=" ">'.e($customer->company->name).'</span>'.
                        '</div>';
                } else {
                    $companyHtml = '<div class="d-flex align-items-center" style="min-height: 1.5rem;"><span class="">غير محدد</span></div>';
                }

                return '<div>'.
                    $companyHtml.
                    '<div class="">'.e($customer->portfolio->name ?? 'غير محدد').'</div>'.
                    '</div>';
            })
            ->addColumn('contact_info', function (Customer $customer) {
                $html = '';
                if ($customer->phone) {
                    $html .= '<div class="small">'.e($customer->phone).'</div>';
                }
                if ($customer->phones && $customer->phones->count() > 1) {
                    $html .= '<div class="small">+'.($customer->phones->count() - 1).' أرقام أخرى</div>';
                }

                return $html ?: '<span>لا يوجد</span>';
            })
            ->addColumn('customer_type_label', function (Customer $customer) {
                return $customer->customer_type === 'company' ? 'شركة' : 'فرد';
            })
            ->addColumn('status', function (Customer $customer) {
                $name = e($customer->customerStatus->name ?? 'غير محدد');
                $color = $customer->customerStatus->color ?? '#6c757d';

                return '<span class="badge" style="background-color: '.$color.'">'.$name.'</span>';
            })
            ->addColumn('relationship_manager', function (Customer $customer) {
                return '<span>'.e($customer->relationshipManager->name ?? 'غير محدد').'</span>';
            })
            ->addColumn('last_contact_result', function (Customer $customer) {
                return '<span>'.e($customer->lastContactResult->name ?? 'غير محدد').'</span>';
            })
            // ->addColumn('status', function (Customer $customer) {
            //     return '<span>' . e($customer->customerStatus->name ?? 'غير محدد') . '</span>';
            // })

            // ->addColumn('last_follow_up', function (Customer $customer) {
            //     if ($customer->last_follow_up) {
            //         return $customer->last_follow_up->locale('ar')->translatedFormat('d F Y');
            //     }
            //     return '<span class="text-muted">لا توجد متابعة</span>';
            // })

            // ->editColumn('created_at_formatted', function (Customer $customer) {
            //     return $customer->created_at->locale('ar')->translatedFormat('d F Y');
            // })
            ->editColumn('created_at_formatted', function (Customer $customer) {
                $days = $customer->created_at->diffInDays(now());

                if ($days == 0) {
                    return 'اليوم';
                } elseif ($days == 1) {
                    return '1';
                } elseif ($days == 2) {
                    return '2';
                } else {
                    return $days.' ';
                }
            })
            ->addColumn('action', function ($row) {
                return view('tenants.business_center.customers.partials.actions', ['customer' => $row])->render();
            })
            ->addColumn('status_color', function (Customer $customer) {
                return $customer->customerStatus ? $customer->customerStatus->color : null;
            })
            ->orderColumn('name', fn ($query, $order) => $query->orderBy('name', $order))
            ->orderColumn('status', fn ($query, $order) => $query->orderBy('customer_status_id', $order))
            ->orderColumn('created_at', fn ($query, $order) => $query->orderBy('created_at', $order))
            ->filterColumn('name', fn ($query, $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->filterColumn('account_number', fn ($query, $keyword) => $query->where('account_number', 'like', "%{$keyword}%"))
            ->filterColumn('identity_info', fn ($query, $keyword) => $query->where('identity_number', 'like', "%{$keyword}%"))
            ->filterColumn('contact_info', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('phones', function ($phoneQuery) use ($keyword) {
                            $phoneQuery->where('phone', 'like', "%{$keyword}%");
                        });
                });
            })
            ->filterColumn('company_portfolio', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('company', function ($companyQuery) use ($keyword) {
                        $companyQuery->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('portfolio', function ($portfolioQuery) use ($keyword) {
                        $portfolioQuery->where('name', 'like', "%{$keyword}%");
                    });
                });
            })
            ->filterColumn('relationship_manager', function ($query, $keyword) {
                $query->whereHas('relationshipManager', function ($managerQuery) use ($keyword) {
                    $managerQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('last_contact_result', function ($query, $keyword) {
                $query->whereHas('lastContactResult', function ($resultQuery) use ($keyword) {
                    $resultQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->whereHas('customerStatus', function ($statusQuery) use ($keyword) {
                    $statusQuery->where('name', 'like', "%{$keyword}%");
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    | Specifies which columns should not be escaped.
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'account_number', 'identity_info', 'company_portfolio', 'contact_info', 'customer_type_label', 'status', 'relationship_manager', 'last_contact_result', 'last_follow_up', 'action'];
    }

    /*
    |--------------------------------------------------------------------------
    | Define All DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        $buttons = [];
        $actionButtons = [];

        if (auth()->user()?->can('customers.send_message')) {
            $actionButtons[] = ['text' => 'إرسال رسالة', 'action' => 'function() { handleSendSMS(); }'];
        }

        $actionButtons[] = ['text' => 'استيراد العملاء', 'action' => 'function() { handleImportCustomers(); }'];

        if (auth()->user()?->can('customers.export')) {
            $actionButtons[] = ['text' => 'تصدير Excel', 'action' => 'function() { handleExport(); }'];
        }

        $actionButtons[] = ['text' => 'تحديث بيانات', 'action' => 'function() { handleBulkUpdateCustomers(); }'];

        if (auth()->user()?->can('customers.delete_all')) {
            $actionButtons[] = ['text' => 'حذف المحدد', 'action' => 'function() { handleMassDelete(); }'];
        }

        if (! empty($actionButtons)) {
            $buttons[] = [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => $actionButtons,
            ];
        }

        if (auth()->user()?->can('customers.create')) {
            $buttons[] = [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة عميل',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return $buttons;
    }
}
