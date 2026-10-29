<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use Carbon\Carbon;

class AuctionsDataTable extends AbstractDataTable
{
    private string $route = 'auctions';

    protected function getTableId(): string
    {
        return 'auctions-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->with(['assignment', 'latestReportGeneration'])
            ->withCount('properties');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false, 'width' => '60px', 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد', 'width' => '200px'],
            ['data' => 'assignment', 'name' => 'assignment_id', 'title' => 'الإسناد', 'width' => '150px'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ البداية', 'width' => '130px'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ النهاية', 'width' => '130px'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'outputs', 'name' => 'outputs', 'title' => 'المخرجات', 'orderable' => false, 'searchable' => false, 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->addColumn('image', function ($row): string {
                $image = $row->cover_image ? tasset($row->cover_image) : null;

                return $this->renderTableImage($image, (string) $row->name);
            })
            ->editColumn('name', function ($row) {
                $showUrl = tenant_route_name($this->route.'.show', $row->id);

                return '<div>
                    <a href="'.$showUrl.'" class="text-body fw-medium d-block">'.e($row->name).'</a>
                    <small class="text-muted">'.e($row->type_label).' - '.e($row->city).'</small>
                </div>';
            })
            ->addColumn('assignment', function ($row) {
                if (! $row->assignment) {
                    return '-';
                }

                return '<a href="'.tenant_route_name('assignments.show', $row->assignment_id).'" class="text-body fw-medium">'.e($row->assignment->name).'</a>';
            })
            ->editColumn('start_date', function ($row) {
                $date = Carbon::parse($row->start_date)->format('Y/m/d');
                $time = $row->start_time ? Carbon::parse($row->start_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->editColumn('end_date', function ($row) {
                $date = Carbon::parse($row->end_date)->format('Y/m/d');
                $time = $row->end_time ? Carbon::parse($row->end_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-info">'.(int) $row->properties_count.'</span>';
            })
            ->addColumn('outputs', function ($row) {
                $gen = $row->latestReportGeneration;
                if (! $gen) {
                    return '<span class="text-muted">—</span>';
                }

                if ($gen->isReady()) {
                    $buttons = [];
                    if (filled($gen->outputs_path)) {
                        $buttons[] = '<a href="'.tenant_route_name('auctions.reports.download', ['auction' => $row->id, 'type' => 'outputs']).'" class="badge bg-secondary me-1" title="تحميل مخرجات المزاد"><i class="ti ti-file-text me-1"></i>المخرجات</a>';
                    }
                    if (filled($gen->report_path)) {
                        $buttons[] = '<a href="'.tenant_route_name('auctions.reports.download', ['auction' => $row->id, 'type' => 'report']).'" class="badge bg-secondary" title="تحميل تقرير المزاد"><i class="ti ti-file-analytics me-1"></i>التقرير</a>';
                    }

                    return ! empty($buttons) ? implode('', $buttons) : '<span class="badge bg-success">جاهز</span>';
                }

                if ($gen->isProcessing() || $gen->status === 'queued') {
                    return '<span class="badge bg-info"><i class="ti ti-loader ti-spin me-1"></i>قيد الإعداد</span>';
                }

                if ($gen->isFailed()) {
                    return '<span class="badge bg-danger" title="'.e($gen->publicErrorMessage()).'"><i class="ti ti-alert-triangle me-1"></i>فشل</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->editColumn('is_active', function ($row): string {
                $canEdit = auth('tenant')->user()?->can('auctions.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('auctions.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="auction_status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة المزاد"></div></div>';
                }

                $statusClass = $row->is_active ? 'bg-success' : 'bg-danger';
                $statusText = $row->is_active ? 'نشط' : 'متوقف';

                return '<span class="badge '.$statusClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenant.auctions.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'assignment', 'start_date', 'end_date', 'properties_count', 'is_active', 'outputs', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active' || $request->status === '1') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive' || $request->status === 'suspended' || $request->status === '0') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->integer('assignment_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->string('city')->toString());
        }

        return $query;
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('auctions.delete')) {
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

    private function getAddButton(): array
    {
        if (auth('tenant')->user()?->can('auctions.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة مزاد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
