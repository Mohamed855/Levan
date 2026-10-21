<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Assignment;

class AssignmentDataTable extends AbstractDataTable
{
    private string $route = 'tenant.assignments';

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
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row): string => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('assignment_number', fn ($row): string => $this->copyableCode((string) $row->assignment_number))
            ->addColumn('image', function ($row): string {
                $attachment = $row->assignmentImage;
                $image = $attachment ? tasset($attachment->file_path) : ($row->entity_logo ? tasset($row->entity_logo) : null);

                return $this->renderTableImage($image, (string) $row->name);
            })
            ->editColumn('name', fn ($row): string => '<a href="'.route($this->route.'.show', $row->id).'" class="text-body fw-medium">'.e($row->name).'</a>')
            ->editColumn('category', fn ($row): string => '<span class="badge bg-label-primary">'.e($row->category_label).'</span>')
            ->editColumn('properties_count', fn ($row): string => '<span class="badge bg-label-info">'.(int) $row->properties_count.'</span>')
            ->editColumn('auctions_count', fn ($row): string => '<span class="badge bg-secondary">'.(int) $row->auctions_count.'</span>')
            ->editColumn('start_date', fn ($row): string => $row->start_date?->format('Y-m-d') ?? '-')
            ->editColumn('end_date', fn ($row): string => $row->end_date?->format('Y-m-d') ?? '-')
            ->editColumn('status', function ($row): string {
                $canEdit = auth('tenant')->user()?->can('assignments.edit') ?? true;
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = route($this->route.'.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="assignment_status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة الإسناد"></div></div>';
                }

                return '<span class="badge '.$row->status_badge_class.'">'.e($row->status_label).'</span>';
            })
            ->addColumn('actions', function ($row): string {
                return view('tenant.assignments.action', ['row' => $row, 'route' => 'assignments'])->render();
            });
    }

    private function copyableCode(string $code): string
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div class="d-inline-flex align-items-center gap-1 copyable-code-wrapper"
                  data-code="%s"
                  title="اضغط للنسخ">
                <code class="copyable-code">%s</code>
                <i class="ti ti-copy text-muted copy-icon"></i>
            </div>',
            $escaped,
            $escaped
        );
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'assignment_number', 'image', 'name', 'category', 'properties_count', 'auctions_count', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
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

        if (auth('tenant')->user()?->can('assignments.delete') ?? true) {
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
        if (auth('tenant')->user()?->can('assignments.create') ?? true) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة إسناد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }

    protected function parameters(): array
    {
        $parameters = parent::parameters();
        $parameters['responsive'] = false;
        $parameters['columnDefs'] = [$parameters['columnDefs'][0]];

        return $parameters;
    }
}
