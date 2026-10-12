<?php

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use Carbon\Carbon;

class AuctionsDataTable extends AbstractDataTable
{
    private string $route = "auctions";

    protected function getTableId(): string
    {
        return "auctions-table";
    }

    protected function resource()
    {
        return Auction::query()
            ->with(['assignment'])
            ->withCount('properties');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '40px'],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false, 'width' => '70px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد', 'width' => '200px'],
            ['data' => 'platform_name', 'name' => 'platform_name', 'title' => 'المنصة / القاعة', 'width' => '150px'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ البداية', 'width' => '130px'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ النهاية', 'width' => '130px'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'outputs', 'name' => 'outputs', 'title' => 'المخرجات', 'orderable' => false, 'searchable' => false, 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '100px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('image', function ($row): string {
                return $this->renderTableImage(
                    $row->cover_image ? tasset($row->cover_image) : null,
                    (string) $row->name,
                );
            })
            ->editColumn('name', function ($row) {
                $showUrl = tenant_route_name($this->route . '.show', $row->id);

                return '<div>
                    <a href="' . $showUrl . '" class="text-body fw-medium d-block">' . e($row->name) . '</a>
                    <small class="text-muted">' . e($row->type_label) . ' - ' . e($row->city) . '</small>
                </div>';
            })
            ->editColumn('platform_name', function ($row) {
                return e($row->platform_name ?: ($row->supervising_entity ?: '-'));
            })
            ->editColumn('start_date', function ($row) {
                $date = Carbon::parse($row->start_date)->format('Y/m/d');
                $time = $row->start_time ? Carbon::parse($row->start_time)->format('h:i A') : '';
                return '<div>' . $date . '</div><small class="text-muted">' . $time . '</small>';
            })
            ->editColumn('end_date', function ($row) {
                $date = Carbon::parse($row->end_date)->format('Y/m/d');
                $time = $row->end_time ? Carbon::parse($row->end_time)->format('h:i A') : '';
                return '<div>' . $date . '</div><small class="text-muted">' . $time . '</small>';
            })
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-label-info">' . (int) $row->properties_count . '</span>';
            })
            ->editColumn('status', function ($row) {
                $canEdit = auth('tenant')->user()?->can('auctions.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('auctions.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center align-items-center gap-1">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" id="auction_status_' . $row->id . '" data-url="' . e($url) . '" data-active-value="active" data-inactive-value="inactive" ' . $checked . ' aria-label="تبديل حالة المزاد">
                        </div>
                        <span class="badge ' . $row->status_badge_class . ' ms-1">' . e($row->status_label) . '</span>
                    </div>';
                }

                return '<span class="badge ' . $row->status_badge_class . '">' . e($row->status_label) . '</span>';
            })
            ->addColumn('outputs', function ($row) {
                return '<div class="d-flex justify-content-center gap-1">
                    <span class="badge bg-secondary p-1" title="بروشور"><i class="ti ti-file-description ti-xs"></i></span>
                    <span class="badge bg-secondary p-1" title="شاشة العرض"><i class="ti ti-device-desktop ti-xs"></i></span>
                    <span class="badge bg-secondary p-1" title="باركود"><i class="ti ti-qrcode ti-xs"></i></span>
                    <span class="badge bg-secondary p-1" title="صور"><i class="ti ti-photo ti-xs"></i></span>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.auctions.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'start_date', 'end_date', 'properties_count', 'status', 'outputs', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform_name')) {
            $query->where('platform_name', 'like', '%' . $request->platform_name . '%');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
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
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => 'function() { handleMassDelete(); }',
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        if (auth('tenant')->user()?->can('auctions.create')) {
            return [
                'text'      => '<i class="ti ti-plus me-1"></i> إضافة مزاد',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
            ];
        }

        return [];
    }
}
