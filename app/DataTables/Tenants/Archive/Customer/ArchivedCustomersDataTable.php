<?php

namespace App\DataTables\Tenants\Archive\Customer;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\Archive\Customer\CustomerArchiveService;
use Carbon\Carbon;

class ArchivedCustomersDataTable extends AbstractDataTable
{
    private $route = "archive.customers";
    private $customerArchiveService;

    public function __construct(CustomerArchiveService $customerArchiveService)
    {
        $this->customerArchiveService = $customerArchiveService;
    }

    /*
    |--------------------------------------------------------------------------
    | بناء جدول البيانات
    |--------------------------------------------------------------------------
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
    | معرف الجدول
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return "archived-customers-table";
    }

    /*
    |--------------------------------------------------------------------------
    | استعلام البيانات الأساسي - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ استخدام الخدمة المحسّنة مع Eager Loading
    */
    protected function resource()
    {
        return $this->customerArchiveService->getArchivedCustomersQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | تعريف أعمدة الجدول - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ تم حذف عمود المديونية لتحسين الأداء
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'customers.id', 'title' => 'ID', 'visible' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => $this->getCheckboxHeader(),
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'createdCell' => "function(td) { $(td).css('padding-left', '38px'); }"
            ],
            ['data' => 'name', 'name' => 'customers.name', 'title' => 'اسم العميل', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'account_number', 'name' => 'customers.account_number', 'title' => 'رقم الحساب', 'width' => '120px'],
            ['data' => 'customer_type', 'name' => 'customers.customer_type', 'title' => 'النوع', 'width' => '100px'],
            ['data' => 'company_portfolio', 'name' => 'company_portfolio', 'title' => 'الشركة/المحفظة', 'width' => '180px', 'orderable' => false, 'searchable' => false],
            ['data' => 'debt_amount', 'name' => 'debt_amount', 'title' => 'المديونية', 'width' => '120px', 'orderable' => false],
            ['data' => 'relationship_manager', 'name' => 'relationship_manager', 'title' => 'مسؤول العلاقة', 'width' => '150px', 'orderable' => false, 'searchable' => false],
            ['data' => 'last_contact_result', 'name' => 'last_contact_result', 'title' => 'نتيجة الاتصال', 'width' => '120px', 'orderable' => false, 'searchable' => false],
            ['data' => 'deleted_at', 'name' => 'customers.deleted_at', 'title' => 'تاريخ الأرشفة', 'width' => '120px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة الفلاتر والبحث العام - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ تحسين البحث باستخدام العلاقات المحملة مسبقاً
    */
    private function applyFilters($datatable)
    {
        $datatable->filter(function ($query) {
            $globalSearch = request()->input('search.value');

            if (!empty($globalSearch)) {
                $cleanSearch = trim(preg_replace('/\s+/', ' ', $globalSearch));
                $searchWords = explode(' ', $cleanSearch);

                $query->where(function ($q) use ($cleanSearch, $searchWords) {
                    $q->where('customers.name', 'like', "%{$cleanSearch}%")
                        ->orWhere('customers.account_number', 'like', "%{$cleanSearch}%");

                    if (count($searchWords) > 1) {
                        $q->orWhere(function ($subQuery) use ($searchWords) {
                            foreach ($searchWords as $word) {
                                if (strlen($word) >= 2) {
                                    $subQuery->where('customers.name', 'like', "%{$word}%");
                                }
                            }
                        });
                    }

                    $q->orWhereHas('company', function ($companyQuery) use ($cleanSearch) {
                        $companyQuery->where('name', 'like', "%{$cleanSearch}%");
                    })
                        ->orWhereHas('portfolio', function ($portfolioQuery) use ($cleanSearch) {
                            $portfolioQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('relationshipManager', function ($managerQuery) use ($cleanSearch) {
                            $managerQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('lastContactResult', function ($resultQuery) use ($cleanSearch) {
                            $resultQuery->where('name', 'like', "%{$cleanSearch}%");
                        });
                });
                return;
            }

            if (request()->filled('customer_type')) {
                $query->where('customers.customer_type', request('customer_type'));
            }

            if (request()->filled('company_id')) {
                $query->where('customers.company_id', request('company_id'));
            }

            if (request()->filled('portfolio_id')) {
                $query->where('customers.portfolio_id', request('portfolio_id'));
            }

            if (request()->filled('relationship_manager_id')) {
                $query->where('customers.relationship_manager_id', request('relationship_manager_id'));
            }

            if (request()->filled('last_contact_result_id')) {
                $query->where('customers.last_contact_result_id', request('last_contact_result_id'));
            }
        });

        return $datatable;
    }

    /*
    |--------------------------------------------------------------------------
    | إضافة الأعمدة المخصصة - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ حذف عمود المديونية
    | ✅ استخدام العلاقات المحملة مسبقاً
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('name', function ($row) {
                return '<div>
                    <span class="fw-medium">' . e($row->name) . '</span>
                </div>';
            })
            ->editColumn('account_number', function ($row) {
                return '<span class="fw-medium">' . e($row->account_number ?? 'غير محدد') . '</span>';
            })
            ->editColumn('customer_type', function ($row) {
                $typeText = $row->customer_type === 'individual' ? 'فردي' : 'شركة';
                $badgeClass = $row->customer_type === 'individual' ? 'bg-label-primary' : 'bg-label-info';
                return '<span class="badge ' . $badgeClass . '">' . $typeText . '</span>';
            })
            ->addColumn('company_portfolio', function (Customer $customer) {
                // ✅ استخدام العلاقة المحملة مسبقاً
                $companyHtml = '';
                if ($customer->company) {
                    $logoHtml = '';
                    if ($customer->company->logo_url) {
                        $logoHtml = '<img src="' . $customer->company->logo_url . '" 
                             alt="' . e($customer->company->name) . '" 
                             style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px; margin-right: 8px; vertical-align: middle;">';
                    }
                    $companyHtml = '<div class="d-flex align-items-center justify-content-start" style="min-height: 24px;">' .
                        $logoHtml .
                        '<span class="small flex-grow-1">' . e($customer->company->name) . '</span>' .
                        '</div>';
                } else {
                    $companyHtml = '<div class="d-flex align-items-center" style="min-height: 24px;"><span class="small">غير محدد</span></div>';
                }

                return '<div>' .
                    $companyHtml .
                    '<div class="small">' . e($customer->portfolio->name ?? 'غير محدد') . '</div>' .
                    '</div>';
            })
            ->editColumn('debt_amount', function ($row) {
                // ✅ حساب المديونية لكل عميل في الجدول
                $remainingDebt = $this->customerArchiveService->getCustomerDebt($row->id);
                return '<span class="fw-medium">' . number_format($remainingDebt, 2) . ' ريال</span>';
            })
            // ✅ تم حذف عمود debt_amount تماماً
            ->editColumn('relationship_manager', function ($row) {
                // ✅ استخدام العلاقة المحملة مسبقاً
                return '<span>' . e($row->relationshipManager->name ?? 'غير محدد') . '</span>';
            })
            ->editColumn('last_contact_result', function ($row) {
                // ✅ استخدام العلاقة المحملة مسبقاً
                $resultName = $row->lastContactResult ? $row->lastContactResult->name : 'غير محدد';
                return '<span class="badge bg-secondary">' . e($resultName) . '</span>';
            })
            ->editColumn('deleted_at', function ($row) {
                return '<div>' . Carbon::parse($row->deleted_at)->format('Y-m-d') . '</div>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.customers.action', ['id' => $row->id, 'route' => $this->route])->render();
            })
            ->filterColumn('name', fn($query, $keyword) => $query->where('customers.name', 'like', "%{$keyword}%"))
            ->filterColumn('account_number', fn($query, $keyword) => $query->where('customers.account_number', 'like', "%{$keyword}%"))
            ->filterColumn('customer_type', fn($query, $keyword) => $query->where('customers.customer_type', 'like', "%{$keyword}%"))
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
            });
    }

    /*
    |--------------------------------------------------------------------------
    | الأعمدة التي تحتوي على HTML خام
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'account_number', 'customer_type', 'company_portfolio', 'debt_amount', 'relationship_manager', 'last_contact_result', 'deleted_at', 'actions'];
    }

    /*
    |--------------------------------------------------------------------------
    | أزرار الجدول
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
    | قائمة الإجراءات المنسدلة
    |--------------------------------------------------------------------------
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('customers_archive.restore')) {
            $buttons[] = [
                'text'      => 'استرجاع المحدد',
                'className' => 'btn btn-default btn-restore-selected',
                'action'    => "function() { handleMassRestore(); }",
            ];
        }

        if (auth()->user()?->can('customers_archive.force_delete')) {
            $buttons[] = [
                'text'      => 'حذف نهائي للمحدد',
                'className' => 'btn btn-default btn-force-delete-selected',
                'action'    => "function() { handleMassForceDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
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
}
