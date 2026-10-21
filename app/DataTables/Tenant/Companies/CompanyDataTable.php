<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Companies;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Company;

class CompanyDataTable extends AbstractDataTable
{
    private string $page = 'tenant.companies';

    private string $route = 'tenant.companies';

    protected function getTableId(): string
    {
        return 'companies-table';
    }

    protected function resource()
    {
        return Company::query()->with('authorizedPeople');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'logo', 'name' => 'logo_path', 'title' => 'الشعار', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '70px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الشركة'],
            ['data' => 'authorized_person', 'name' => 'authorizedPeople.name', 'title' => 'المفوض الرئيسي', 'orderable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('logo', function (Company $row) {
                if (! $row->logo_path) {
                    return '<span class="avatar avatar-sm bg-label-secondary rounded"><i class="ti ti-building"></i></span>';
                }
                $url = asset('storage/'.$row->logo_path);
                return sprintf(
                    '<img src="%s" alt="%s" class="rounded" style="max-height: 36px; max-width: 50px; object-fit: contain;">',
                    e($url),
                    e($row->name)
                );
            })
            ->editColumn('name', function (Company $row) {
                $url = route($this->route.'.show', $row->id);
                return '<a href="'.$url.'" class="fw-bold text-heading">'.e($row->name).'</a>';
            })
            ->addColumn('authorized_person', function (Company $row) {
                $person = $row->authorizedPeople->firstWhere('is_default', true) ?? $row->authorizedPeople->first();
                if (! $person) {
                    return '<span class="text-muted">-</span>';
                }

                return '<div><span class="fw-medium">'.e($person->name).'</span>'.($person->phone ? '<small class="text-muted d-block font-monospace" dir="ltr">'.e($person->phone).'</small>' : '').'</div>';
            })
            ->editColumn('created_at', fn (Company $row) => $row->created_at?->format('Y-m-d') ?? '-')
            ->editColumn('status', function (Company $row) {
                if (! auth('tenant')->user()?->can('companies.edit')) {
                    return $row->status === 'active' ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطل</span>';
                }

                $checked = $row->status === 'active' ? 'checked' : '';
                $url = route($this->route.'.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة الشركة">
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

        if ($request->filled('status')) {
            $val = $request->status;
            if ($val === 'active' || $val === 'inactive') {
                $query->where('status', $val);
            }
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'logo', 'name', 'authorized_person', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name'];
    }

    protected function getCustomOrder(): array
    {
        return [2, 'asc'];
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
                'columns' => [2, 3, 4],
            ]],
        ];

        if (auth('tenant')->user()?->can('companies.delete')) {
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
        if (auth('tenant')->user()?->can('companies.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة شركة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
