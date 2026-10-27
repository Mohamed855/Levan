<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Assignments\Assignment;

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
            ->with('assignmentImage')
            ->withCount(['properties', 'auctions']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'assignment_number', 'name' => 'assignment_number', 'title' => 'رقم الإسناد'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
            ['data' => 'issuing_entity', 'name' => 'issuing_entity', 'title' => 'الجهة'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'category', 'name' => 'category', 'title' => 'التصنيف'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'className' => 'text-center'],
            ['data' => 'auctions_count', 'name' => 'auctions_count', 'title' => 'عدد المزادات', 'className' => 'text-center'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ البداية'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ النهاية'],
            ['data' => 'status', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row): string => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('assignment_number', fn ($row): string => $this->copyableCode($row->assignment_number))
            ->addColumn('image', function ($row): string {
                $attachment = $row->assignmentImage;
                $image = $attachment ? tasset($attachment->file_path) : null;

                return $this->renderTableImage($image, (string) $row->name);
            })
            ->editColumn('name', fn ($row): string => '<a href="'.tenant_route_name($this->route.'.show', $row->id).'" class="text-body fw-medium">'.e($row->name).'</a>')
            ->editColumn('category', fn ($row): string => '<span class="badge bg-primary">'.e($row->category_label).'</span>')
            ->editColumn('properties_count', fn ($row): string => '<span class="badge bg-info">'.(int) $row->properties_count.'</span>')
            ->editColumn('auctions_count', fn ($row): string => '<span class="badge bg-secondary">'.(int) $row->auctions_count.'</span>')
            ->editColumn('start_date', fn ($row): string => $row->start_date?->format('Y-m-d') ?? '-')
            ->editColumn('end_date', fn ($row): string => $row->end_date?->format('Y-m-d') ?? '-')
            ->editColumn('status', function ($row): string {
                $canEdit = auth('tenant')->user()?->can('assignments.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('assignments.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="asg_status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة الإسناد"></div></div>';
                }

                $statusClass = $row->is_active ? 'bg-success' : 'bg-danger';
                $statusText = $row->is_active ? 'نشط' : 'متوقف';

                return '<span class="badge '.$statusClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', fn ($row): string => view('tenant.assignments.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'assignment_number', 'image', 'name', 'category', 'properties_count', 'auctions_count', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        return $query
            ->when($request->filled('status'), function ($builder) use ($request) {
                if ($request->status === 'active' || $request->status === '1') {
                    $builder->where('is_active', true);
                } elseif ($request->status === 'inactive' || $request->status === 'suspended' || $request->status === '0') {
                    $builder->where('is_active', false);
                }
            })
            ->when($request->filled('category'), fn ($builder) => $builder->where('category', $request->category))
            ->when($request->filled('start_date'), fn ($builder) => $builder->whereDate('start_date', $request->start_date))
            ->when($request->filled('end_date'), fn ($builder) => $builder->whereDate('end_date', $request->end_date))
            ->when($request->filled('city'), fn ($builder) => $builder->where('city', $request->city));
    }

    protected function filterableColumns(): array
    {
        return ['assignment_number', 'name', 'issuing_entity', 'city'];
    }

    protected function buttons(): array
    {
        return [$this->getActionsDropdownButton(), $this->getAddButton()];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']];
        if (auth('tenant')->user()?->can('assignments.delete')) {
            $buttons[] = ['text' => 'حذف المحدد', 'className' => 'btn btn-default btn-delete-selected', 'action' => 'function() { handleMassDelete(); }'];
        }

        return ['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => $buttons];
    }

    private function getAddButton(): array
    {
        if (! auth('tenant')->user()?->can('assignments.create')) {
            return [];
        }

        return ['text' => '<i class="ti ti-plus me-1"></i> إضافة إسناد', 'className' => 'btn btn-primary btn-add', 'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }"];
    }

    protected function parameters(): array
    {
        $parameters = parent::parameters();
        $parameters['responsive'] = false;
        $parameters['columnDefs'] = [$parameters['columnDefs'][0]];

        return $parameters;
    }
}
