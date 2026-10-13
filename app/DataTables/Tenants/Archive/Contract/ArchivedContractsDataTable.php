<?php

namespace App\DataTables\Tenants\Archive\Contract;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenants\Archive\Contract\ContractArchiveService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ArchivedContractsDataTable extends AbstractDataTable
{
    private $route = 'archive.contracts';

    private $contractArchiveService;

    public function __construct(ContractArchiveService $contractArchiveService)
    {
        $this->contractArchiveService = $contractArchiveService;
    }

    /*
    |--------------------------------------------------------------------------
    | بناء جدول البيانات
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        if ($query instanceof Collection) {
            return datatables()->collection($query)->addIndexColumn();
        }

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
        return 'archived-contracts-table';
    }

    /*
    |--------------------------------------------------------------------------
    | استعلام البيانات الأساسي
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        if (! Schema::hasTable('contracts')) {
            return collect([]);
        }

        return $this->contractArchiveService->getArchivedContractsQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | تعريف أعمدة الجدول
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'contracts.id', 'title' => 'ID', 'visible' => false],
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
            ['data' => 'contract_number', 'name' => 'contracts.contract_number', 'title' => 'رقم العقد', 'width' => '150px', 'className' => 'text-start'],
            ['data' => 'company_info', 'name' => 'company_info', 'title' => 'الشركة', 'width' => '200px', 'orderable' => false, 'searchable' => false],
            ['data' => 'contract_dates', 'name' => 'contract_dates', 'title' => 'تواريخ العقد', 'width' => '180px', 'orderable' => false],
            ['data' => 'status', 'name' => 'contracts.status', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'commission_type', 'name' => 'contracts.commission_type', 'title' => 'نوع العمولة', 'width' => '120px'],
            ['data' => 'deleted_at', 'name' => 'contracts.deleted_at', 'title' => 'تاريخ الأرشفة', 'width' => '120px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة الفلاتر والبحث العام
    |--------------------------------------------------------------------------
    */
    private function applyFilters($datatable)
    {
        $datatable->filter(function ($query) {
            $globalSearch = request()->input('search.value');

            if (! empty($globalSearch)) {
                $cleanSearch = trim(preg_replace('/\s+/', ' ', $globalSearch));
                $searchWords = explode(' ', $cleanSearch);

                $query->where(function ($q) use ($cleanSearch, $searchWords) {
                    $q->where('contracts.contract_number', 'like', "%{$cleanSearch}%");

                    if (count($searchWords) > 1) {
                        $q->orWhere(function ($subQuery) use ($searchWords) {
                            foreach ($searchWords as $word) {
                                if (strlen($word) >= 2) {
                                    $subQuery->where('contracts.contract_number', 'like', "%{$word}%");
                                }
                            }
                        });
                    }

                    $q->orWhereHas('company', function ($companyQuery) use ($cleanSearch) {
                        $companyQuery->where('name', 'like', "%{$cleanSearch}%");
                    });
                });

                return;
            }

            if (request()->filled('status')) {
                $query->where('contracts.status', request('status'));
            }

            if (request()->filled('company_id')) {
                $query->where('contracts.company_id', request('company_id'));
            }

            if (request()->filled('commission_type')) {
                $query->where('contracts.commission_type', request('commission_type'));
            }

            if (request()->filled('auto_renewal')) {
                $query->where('contracts.auto_renewal', (bool) request('auto_renewal'));
            }
        });

        return $datatable;
    }

    /*
    |--------------------------------------------------------------------------
    | إضافة الأعمدة المخصصة
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('contract_number', function ($row) {
                return '<div>
                    <span class="fw-medium text-primary">'.e($row->contract_number).'</span>
                </div>';
            })
            ->editColumn('company_info', function ($row) {
                $companyHtml = '';
                if ($row->company) {
                    $logoHtml = '';
                    if ($row->company->logo_url) {
                        $logoHtml = '<img src="'.$row->company->logo_url.'" 
                             alt="'.e($row->company->name).'" 
                             style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px; margin-right: 8px; vertical-align: middle;">';
                    }
                    $companyHtml = '<div class="d-flex align-items-center justify-content-start" style="min-height: 24px;">'.
                        $logoHtml.
                        '<span class="small flex-grow-1">'.e($row->company->name).'</span>'.
                        '</div>';
                } else {
                    $companyHtml = '<div class="d-flex align-items-center" style="min-height: 24px;"><span class="small">غير محدد</span></div>';
                }

                return $companyHtml;
            })
            ->editColumn('contract_dates', function ($row) {
                $startDate = $row->start_date ? Carbon::parse($row->start_date)->format('Y-m-d') : 'غير محدد';
                $endDate = $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : 'غير محدد';

                return '<div>
                    <div class="small text-success">من: '.$startDate.'</div>
                    <div class="small text-danger">إلى: '.$endDate.'</div>
                </div>';
            })
            ->editColumn('status', function ($row) {
                $labels = [
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
                    'expired' => 'منتهي',
                    'suspended' => 'معلق',
                ];

                $classes = [
                    'active' => 'bg-label-success',
                    'inactive' => 'bg-secondary',
                    'expired' => 'bg-label-danger',
                    'suspended' => 'bg-label-warning',
                ];

                $statusLabel = $labels[$row->status] ?? 'غير محدد';
                $statusClass = $classes[$row->status] ?? 'bg-secondary';

                return '<span class="badge '.$statusClass.'">'.$statusLabel.'</span>';
            })
            ->editColumn('commission_type', function ($row) {
                $labels = [
                    'procedure' => 'حسب الإجراء',
                    'duration' => 'حسب المدة',
                    'delinquency' => 'حسب مدة التعثر',
                ];

                $typeLabel = $labels[$row->commission_type] ?? 'غير محدد';

                return '<span class="badge bg-label-info">'.$typeLabel.'</span>';
            })
            ->editColumn('auto_renewal', function ($row) {
                return $row->auto_renewal
                    ? '<span class="badge bg-label-success">نعم</span>'
                    : '<span class="badge bg-secondary">لا</span>';
            })
            ->editColumn('deleted_at', function ($row) {
                return Carbon::parse($row->deleted_at)->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.contracts.action', ['id' => $row->id, 'route' => $this->route])->render();
            })
            ->filterColumn('contract_number', fn ($query, $keyword) => $query->where('contracts.contract_number', 'like', "%{$keyword}%"))
            ->filterColumn('company_info', function ($query, $keyword) {
                $query->whereHas('company', function ($companyQuery) use ($keyword) {
                    $companyQuery->where('name', 'like', "%{$keyword}%");
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
        return ['checkbox', 'contract_number', 'company_info', 'contract_dates', 'status', 'commission_type', 'auto_renewal', 'actions'];
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }
}
