<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Assignments\IssuingEntity;

class IssuingEntitiesDataTable extends AbstractDataTable
{
    private string $page = 'tenant.issuing-entities';

    private string $route = 'issuing-entities';

    protected function getTableId(): string
    {
        return 'issuing-entities-table';
    }

    protected function resource()
    {
        return IssuingEntity::query()->withCount('assignments');
    }

    protected function getColumns(): array
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => 'اسم الجهة المسندة',
                'className' => 'text-start fw-semibold',
            ],
            [
                'data' => 'assignments_count',
                'name' => 'assignments_count',
                'title' => 'عدد الإسنادات المرتبطة',
                'className' => 'text-center',
                'searchable' => false,
                'orderable' => true,
                'width' => '150px',
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'تاريخ الإضافة',
                'className' => 'text-center',
                'width' => '130px',
            ],
            [
                'data' => 'is_active',
                'name' => 'is_active',
                'title' => 'الحالة',
                'className' => 'text-center',
                'width' => '100px',
            ],
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'className' => 'text-center',
            ],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('assignments_count', function ($row) {
                return '<span class="badge bg-label-primary fs-6">'.$row->assignments_count.'</span>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('Y-m-d') : '-';
            })
            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                $url = tenant_route_name('issuing-entities.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة الجهة المسندة"></div></div>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'assignments_count', 'is_active', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        $status = $request->input('is_active', $request->input('status'));
        if ($status !== null && $status !== '') {
            if ($status === 'active' || $status === '1' || $status === 1) {
                $query->where('is_active', 1);
            } elseif ($status === 'inactive' || $status === '0' || $status === 0) {
                $query->where('is_active', 0);
            } else {
                $query->where('is_active', $status);
            }
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return [
            'name',
        ];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'asc'];
    }

    protected function buttons(): array
    {
        $buttons = [
            $this->getActionsDropdownButton(),
        ];

        $add = $this->getAddButton();
        if (! empty($add)) {
            $buttons[] = $add;
        }

        return $buttons;
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('issuing_entities.delete')) {
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
        if (! auth('tenant')->user()?->can('issuing_entities.create')) {
            return [];
        }

        return [
            'text' => '<i class="ti ti-plus me-1"></i> إضافة جهة مسندة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }
}
