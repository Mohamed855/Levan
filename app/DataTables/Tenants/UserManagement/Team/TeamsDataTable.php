<?php

namespace App\DataTables\Tenants\UserManagement\Team;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\UserMangment\Team\Team;

class TeamsDataTable extends AbstractDataTable
{
    private $route = "teams";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Get Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return Team::query()
            ->select([
                'id',
                'name',
                'description',
                'branch_id',
                'status',
                'created_at'
            ])
            ->with(['branch:id,name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId(): string
    {
        return "teams-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => ''],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المجموعة'],
            ['data' => 'branch', 'name' => 'branch.name', 'title' => 'الفرع', 'orderable' => false, 'searchable' => true],
            ['data' => 'members_count', 'name' => 'members_count', 'title' => 'عدد الأعضاء', 'orderable' => false, 'searchable' => false],
            ['data' => 'leaders_count', 'name' => 'leaders_count', 'title' => 'عدد القادة', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('created_at', fn(Team $team) => $team->created_at->locale('ar')->translatedFormat('d F Y'))
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->editColumn('name', function (Team $team) {
                $url = tenant_route_name('teams.show', $team->id);
                return '<a href="' . $url . '" class="text-primary fw-bold text-decoration-none">' . e($team->name) . '</a>';
            })
            ->addColumn('branch', function (Team $team) {
                if (!$team->branch) {
                    return '<span class="text-muted">-</span>';
                }
                return '<span class="badge bg-secondary">' . e($team->branch->name) . '</span>';
            })
            ->addColumn('members_count', function (Team $team) {
                $count = $team->members()->count();
                $badgeClass = $count > 0 ? 'bg-label-info' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . $count . ' عضو</span>';
            })
            ->addColumn('leaders_count', function (Team $team) {
                $count = $team->leaders()->count();
                $badgeClass = $count > 0 ? 'bg-label-primary' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . $count . ' قائد</span>';
            })
            ->editColumn('status', function (Team $team) {
                $checked = $team->status === 'active' ? 'checked' : '';
                $url = tenant_route_name('teams.toggle-status', ['team' => $team->id]);
                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input js-table-status-toggle"
                                id="status_' . $team->id . '"
                                data-url="' . e($url) . '"
                                ' . $checked . '
                                aria-label="تبديل حالة المجموعة"
                            >
                        </div>
                    </div>
                ';
            })
            ->addColumn('action', fn($row) => view('tenants.users_management.team.action', ['id' => $row->id, 'route' => $this->route])->render());
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'branch', 'members_count', 'leaders_count', 'status', 'action'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Define All DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export btn',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
                ['text' => 'حذف المحدد', 'action' => "function() { handleMassDelete(); }"]
            ],
        ];
    }

    private function getAddButton(): array
    {
        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مجموعة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}
