<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Banks;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bank;

class BankDataTable extends AbstractDataTable
{
    private string $page = 'admin.banks';

    private string $route = 'admin.banks';

    protected function getTableId(): string
    {
        return 'banks-table';
    }

    protected function resource()
    {
        return Bank::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'logo', 'name' => 'logo_path', 'title' => 'الشعار', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم البنك'],
            ['data' => 'account_name', 'name' => 'account_name', 'title' => 'اسم الحساب'],
            ['data' => 'account_number', 'name' => 'account_number', 'title' => 'رقم الحساب'],
            ['data' => 'iban', 'name' => 'iban', 'title' => 'رقم الآيبان (IBAN)'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('logo', function ($row) {
                if (! $row->logo_path) {
                    return '<span class="avatar avatar-sm bg-label-secondary rounded"><i class="ti ti-building-bank"></i></span>';
                }
                $url = asset('storage/'.$row->logo_path);
                return sprintf(
                    '<img src="%s" alt="%s" class="rounded" style="max-height: 36px; max-width: 60px; object-fit: contain;">',
                    e($url),
                    e($row->name)
                );
            })
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('account_number', fn ($row) => $row->account_number ? '<span class="font-monospace">'.e($row->account_number).'</span>' : '<span class="text-muted">-</span>')
            ->editColumn('iban', fn ($row) => '<span class="font-monospace">'.e($row->iban).'</span>')
            ->editColumn('is_active', function ($row) {
                if (! auth('admin')->user()?->can('banks.update')) {
                    return $row->is_active ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.banks.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة البنك">
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
        return ['checkbox', 'logo', 'name', 'account_number', 'iban', 'is_active', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'account_name', 'iban', 'account_number'];
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
                'columns' => [2, 3, 4, 5],
            ]],
        ];

        if (auth('admin')->user()?->can('banks.update')) {
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
        if (auth('admin')->user()?->can('banks.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة حساب بنكي',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
