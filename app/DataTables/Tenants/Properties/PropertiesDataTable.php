<?php

namespace App\DataTables\Tenants\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;

class PropertiesDataTable extends AbstractDataTable
{
    private string $route = "properties";

    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getTableId(): string
    {
        return "properties-table";
    }

    protected function resource()
    {
        $query = Property::query()
            ->with('assignment');

        $this->applyCustomFilters($query);

        return $query;
    }

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
                'width'      => '10px',
                'className'  => 'custom-checkbox',
                'titleAttr'  => 'تحديد الكل',
            ],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار', 'width' => '100px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العقار', 'width' => '170px'],
            ['data' => 'assignment_name', 'name' => 'assignment.name', 'title' => 'الإسناد التابع له', 'width' => '150px'],
            ['data' => 'type', 'name' => 'type', 'title' => 'نوع العقار', 'width' => '110px'],
            ['data' => 'location', 'name' => 'city', 'title' => 'المدينة / الحي', 'width' => '130px'],
            ['data' => 'area', 'name' => 'area', 'title' => 'المساحة', 'width' => '90px'],
            ['data' => 'deed_number', 'name' => 'deed_number', 'title' => 'رقم الصك', 'width' => '120px'],
            ['data' => 'data_status', 'name' => 'data_status', 'title' => 'حالة البيانات', 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('property_number', function ($row) {
                return $this->copyableCode($row->property_number);
            })
            ->editColumn('name', function ($row) {
                $img = $row->main_image ? tasset($row->main_image) : asset('assets/img/illustrations/page-pricing-standard.png');
                $showUrl = tenant_route_name($this->route . '.show', $row->id);

                return '<div class="d-flex align-items-center">
                    <img src="' . $img . '" alt="property" class="rounded me-2" style="width:32px;height:32px;object-fit:cover" />
                    <a href="' . $showUrl . '" class="text-body fw-medium">' . e($row->name) . '</a>
                </div>';
            })
            ->addColumn('assignment_name', function ($row) {
                if (! $row->assignment) {
                    return '-';
                }
                return '<a href="' . tenant_route_name('assignments.show', $row->assignment_id) . '" class="text-primary">' . e($row->assignment->name) . '</a>';
            })
            ->editColumn('type', function ($row) {
                return '<span class="badge bg-label-info">' . e($row->type_label) . '</span>';
            })
            ->addColumn('location', function ($row) {
                return e($row->city . ($row->district ? ' - ' . $row->district : ''));
            })
            ->editColumn('area', function ($row) {
                return number_format((float) $row->area) . ' م²';
            })
            ->editColumn('deed_number', function ($row) {
                return $row->deed_number ? $this->copyableCode($row->deed_number) : '-';
            })
            ->editColumn('data_status', function ($row) {
                return '<span class="badge ' . $row->data_status_badge_class . '">' . e($row->data_status_label) . '</span>';
            })
            ->editColumn('status', function ($row) {
                $canEdit = auth()->user()?->can('properties.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('properties.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="prop_status_' . $row->id . '" data-url="' . e($url) . '" data-active-value="active" data-inactive-value="inactive" ' . $checked . ' aria-label="تبديل حالة العقار"></div></div>';
                }

                $statusClass = $row->is_active ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->is_active ? 'نشط' : 'متوقف';

                return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.properties.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    private function copyableCode(string $code): string
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<div class="d-inline-flex align-items-center gap-1 copyable-code-wrapper"
                  data-code="%s"
                  style="cursor: pointer;"
                  title="اضغط للنسخ">
                <code class="copyable-code" style="padding: 2px 6px; background: #f1f3f5; border-radius: 4px; font-weight: 600;">%s</code>
                <i class="ti ti-copy text-muted copy-icon" style="font-size: 14px;"></i>
            </div>',
            $escaped,
            $escaped
        );
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'property_number', 'name', 'assignment_name', 'type', 'deed_number', 'data_status', 'status', 'actions'];
    }

    protected function applyCustomFilters($query): void
    {
        $request = request();

        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('data_status')) {
            $query->where('data_status', $request->data_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    }
}
