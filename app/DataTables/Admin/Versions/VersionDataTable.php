<?php

namespace App\DataTables\Admin\Versions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Version\Version;

class VersionDataTable extends AbstractDataTable
{
    private $page = 'admin.versions';

    private $route = 'admin.versions';

    // table id
    protected function getTableId()
    {
        return 'versions-table';
    }

    protected function resource()
    {
        return Version::query();
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
            ['data' => 'version',       'name' => 'version',     'title' => 'رقم الإصدارة'],
            ['data' => 'stage',         'name' => 'stage',       'title' => 'المرحلة'],
            ['data' => 'file_path',     'name' => 'file_path',   'title' => 'مسار الإصدارة'],
            ['data' => 'released_at',   'name' => 'released_at', 'title' => 'تاريخ الإصدار'],
            ['data' => 'is_active',     'name' => 'is_active',   'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions',       'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('version', function ($row) {
                return $this->routeName(route($this->route.'.show', $row->id), $row->version);
            })

            ->editColumn('stage', fn ($row) => $this->StageBadge($row->stage))

            ->editColumn('released_at', fn ($row) => $row->released_at ? $row->released_at->format('Y-m-d') : '-')

            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.versions.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة الإصدار"></div></div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'version', 'stage', 'is_active', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

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

        if (auth()->user()?->can('versions.delete')) {
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
        if (auth()->user()?->can('versions.create')) {
            return [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة إصدارة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
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
