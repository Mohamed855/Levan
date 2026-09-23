<?php

namespace App\DataTables\Admin\Versions;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Version\Version;

class VersionDataTable extends AbstractDataTable
{
    private $page  = "admin.versions";
    private $route = "admin.versions";


    // table id
    protected function getTableId()
    {
        return "versions-table";
    }


    protected function resource()
    {
        return Version::query();
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
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'version',       'name' => 'version',     'title' => 'رقم الإصدارة'],
            ['data' => 'stage',         'name' => 'stage',       'title' => 'المرحلة'],
            ['data' => 'file_path',     'name' => 'file_path',   'title' => 'مسار الإصدارة'],
            ['data' => 'released_at',   'name' => 'released_at', 'title' => 'تاريخ الإصدار'],
            ['data' => 'is_active',     'name' => 'is_active',   'title' => 'الحالة'],
            ['data' => 'actions',       'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('version', function ($row) {
                return $this->routeName(route($this->route . '.show', $row->id), $row->version);
            })

            ->editColumn('stage', fn($row) => $this->StageBadge($row->stage))

            ->editColumn('released_at', fn($row) => $row->released_at ? $row->released_at->format('Y-m-d') : '-')

            ->editColumn(
                'is_active',
                fn($row) => $row->is_active
                    ? '<span class="badge bg-success">مفعلة</span>'
                    : '<span class="badge bg-danger">غير مفعلة</span>'
            )

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'version', 'stage', 'is_active', 'actions'];
    }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return ['version', 'name', 'stage', 'file_path'];
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

        if (auth()->user()?->can('حذف إصدارة')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        if (auth()->user()?->can('إضافة إصدارة')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة إصدارة',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        } else {
            return [];
        }
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function StageBadge($stage): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $stage->color(),
            $stage->label()
        );
    }
}
