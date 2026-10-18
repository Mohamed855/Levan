<?php

namespace App\DataTables\Admin\Setting;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ControlDomain\ControlDomain;

class SettingDomainDataTable extends AbstractDataTable
{
    private $page = 'admin.settings.domains';

    private $route = 'admin.settings.domains';

    protected function getTableId()
    {
        return 'domain-setting-table';
    }

    protected function resource()
    {
        return ControlDomain::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'domain_extension',  'name' => 'domain_extension',   'title' => 'امتداد الدومين',   'className' => 'text-start'],
            ['data' => 'price',             'name' => 'price',              'title' => 'السعر',             'className' => 'text-start'],
            ['data' => 'created_at',         'name' => 'created_at',          'title' => 'تاريخ الاضافة'],
            ['data' => 'is_active',         'name' => 'is_active',          'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',         'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.settings.domains.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة امتداد الدومين"></div></div>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'code', 'discount_type', 'is_active', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', 1);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', 0);
            } else {
                $query->where('is_active', $val);
            }
        }

        return $query;
    }

    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'domain_extension',
        ];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    // Buttons
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

        // if (auth()->user()?->can('coupons.delete')) {
        $buttons[] = [
            'text' => 'حذف المحدد',
            'className' => 'btn btn-default btn-delete-selected',
            'action' => 'function() { handleMassDelete(); }',
        ];
        // }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        // if (!auth()->user()?->can('coupons.create')) {
        //     return [];
        // }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة امتداد',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
        ];
    }
}
