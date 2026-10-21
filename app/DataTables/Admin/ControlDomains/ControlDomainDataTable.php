<?php

declare(strict_types=1);

namespace App\DataTables\Admin\ControlDomains;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ControlDomain;

class ControlDomainDataTable extends AbstractDataTable
{
    private string $page = 'admin.control-domains';

    private string $route = 'admin.control-domains';

    protected function getTableId(): string
    {
        return 'control-domains-table';
    }

    protected function resource()
    {
        return ControlDomain::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'domain', 'name' => 'domain', 'title' => 'النطاق / الامتداد'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('domain', fn ($row) => '<span class="fw-bold text-heading font-monospace" dir="ltr">'.e($row->domain).'</span>')
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i') ?? '-')
            ->editColumn('is_active', function ($row) {
                if (! auth('admin')->user()?->can('control_domains.update')) {
                    return $row->is_active ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.control-domains.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة النطاق">
                        </div>
                    </div>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', true);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'domain', 'is_active', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['domain'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'asc'];
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
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [1, 2],
            ]],
        ];

        if (auth('admin')->user()?->can('control_domains.update')) {
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
        if (auth('admin')->user()?->can('control_domains.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة نطاق',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
