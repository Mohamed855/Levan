<?php

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Assignments\Assignment;
use Carbon\Carbon;

class AssignmentsDataTable extends AbstractDataTable
{
    private string $route = 'assignments';

    protected function getTableId(): string
    {
        return 'assignments-table';
    }

    protected function resource()
    {
        return Assignment::query()
            ->withCount(['properties', 'auctions']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '40px'],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'assignment_number', 'name' => 'assignment_number', 'title' => 'رقم الإسناد', 'width' => '130px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الإسناد', 'width' => '180px'],
            ['data' => 'issuing_entity', 'name' => 'issuing_entity', 'title' => 'الجهة المسندة', 'width' => '140px'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة', 'width' => '100px'],
            ['data' => 'category', 'name' => 'category', 'title' => 'التصنيف', 'width' => '110px'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'auctions_count', 'name' => 'auctions_count', 'title' => 'عدد المزادات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ الإسناد', 'width' => '110px'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ الانتهاء', 'width' => '110px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('assignment_number', function ($row) {
                return $this->copyableCode($row->assignment_number);
            })
            ->editColumn('name', function ($row) {
                $img = $row->entity_logo ? tasset($row->entity_logo) : asset('assets/img/illustrations/page-pricing-enterprise.png');
                $showUrl = tenant_route_name($this->route.'.show', $row->id);

                return '<div class="d-flex align-items-center">
                    <img src="'.$img.'" alt="logo" class="rounded me-2" style="width:28px;height:28px;object-fit:cover" />
                    <a href="'.$showUrl.'" class="text-body fw-medium">'.e($row->name).'</a>
                </div>';
            })
            ->editColumn('category', function ($row) {
                return '<span class="badge bg-label-primary">'.e($row->category_label).'</span>';
            })
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-label-info">'.(int) $row->properties_count.'</span>';
            })
            ->editColumn('auctions_count', function ($row) {
                return '<span class="badge bg-secondary">'.(int) $row->auctions_count.'</span>';
            })
            ->editColumn('start_date', function ($row) {
                return $row->start_date ? Carbon::parse($row->start_date)->format('Y-m-d') : '-';
            })
            ->editColumn('end_date', function ($row) {
                return $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : '-';
            })
            ->editColumn('status', function ($row) {
                $canEdit = auth('tenant')->user()?->can('assignments.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('assignments.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة الإسناد"></div></div>';
                }

                $statusClass = $row->is_active ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->is_active ? 'نشط' : 'متوقف';

                return '<span class="badge '.$statusClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.assignments.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    private function copyableCode(string $code): string
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div class="d-inline-flex align-items-center gap-1 copyable-code-wrapper"
                  data-code="%s"
                  style="cursor: pointer;"
                  title="اضغط لنسخ الرقم">
                <code class="copyable-code" style="padding: 2px 6px; background: #f1f3f5; border-radius: 4px; font-weight: 600;">%s</code>
                <i class="ti ti-copy text-muted copy-icon" style="font-size: 14px;"></i>
            </div>',
            $escaped,
            $escaped
        );
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'assignment_number', 'name', 'category', 'properties_count', 'auctions_count', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('issuing_entity')) {
            $query->where('issuing_entity', 'like', '%'.$request->issuing_entity.'%');
        }

        if ($request->filled('assignment_number')) {
            $query->where('assignment_number', 'like', '%'.$request->assignment_number.'%');
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

        if (auth('tenant')->user()?->can('assignments.delete')) {
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
        if (auth('tenant')->user()?->can('assignments.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة إسناد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
