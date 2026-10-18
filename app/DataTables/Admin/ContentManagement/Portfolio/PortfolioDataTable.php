<?php

namespace App\DataTables\Admin\ContentManagement\Portfolio;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ContentManagement\Portfolio;

class PortfolioDataTable extends AbstractDataTable
{
    private $page = 'admin.content_management.portfolio';

    private $route = 'admin.content_management.portfolio';

    // table id
    protected function getTableId()
    {
        return 'portfolio-table';
    }

    protected function resource()
    {
        return Portfolio::query();
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

            ['data' => 'name',       'name' => 'name',       'title' => 'اسم المحفظة',     'className' => 'text-start'],
            ['data' => 'logo',       'name' => 'logo',       'title' => 'الشعار',          'className' => 'text-start'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء',  'className' => 'text-start'],
            ['data' => 'status',     'name' => 'status',     'title' => 'الحالة', 'className' => 'text-center'],

            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
            ],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        return $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('logo', function ($row) {
                if ($row->logo) {
                    return '<img src="'.asset('storage/'.$row->logo).'" alt="'.$row->name.'" style="max-width:60px; max-height:40px; border-radius:4px; object-fit:cover;">';
                }

                return '<span class="badge bg-secondary">بدون شعار</span>';
            })

            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked' : '';
                $url = route('admin.content_management.portfolio.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة المحفظة"></div></div>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at
                    ? $row->created_at->format('d/m/Y')
                    : '-';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.actions', [
                    'row' => $row,
                    'route' => $this->route,
                ])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'logo', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $val = $request->status;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('status', 1);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('status', 0);
            } else {
                $query->where('status', $val);
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
            ['extend' => 'copy',  'text' => 'نسخ'],
            [
                'extend' => 'excel',
                'text' => 'إكسل',
                'exportOptions' => [
                    'columns' => [6, 5, 4, 3, 2, 1],
                ],
            ],
            [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => 'function() { handleMassDelete(); }',
            ],
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة محفظة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
        ];
    }
}
