<?php

declare(strict_types=1);

namespace App\DataTables\Admin\ActivityLogs;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ActivityLog;
use App\Traits\ActivityLogPresenterTrait;
use Carbon\Carbon;

class ActivityLogDataTable extends AbstractDataTable
{
    use ActivityLogPresenterTrait;

    private string $page = 'admin.activity-logs';

    private string $route = 'admin.activity-logs';

    protected function getTableId(): string
    {
        return 'activity-logs-table';
    }

    protected function resource()
    {
        return ActivityLog::with(['causer', 'subject'])->select([
            'id',
            'log_name',
            'description',
            'subject_id',
            'subject_type',
            'causer_id',
            'causer_type',
            'event',
            'properties',
            'created_at',
        ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'log_name', 'name' => 'log_name', 'title' => 'نوع السجل'],
            ['data' => 'description', 'name' => 'description', 'title' => 'الوصف والحدث'],
            ['data' => 'causer_name', 'name' => 'causer.name', 'title' => 'المستخدم الفاعل'],
            ['data' => 'subject_display', 'name' => 'subject_id', 'title' => 'العنصر المتأثر', 'orderable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التوقيت'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '90px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('log_name', function ($row) {
                return '<span class="badge bg-label-primary">'.e($this->activityLogNameLabel($row->log_name)).'</span>';
            })
            ->editColumn('description', function ($row) {
                return '<span class="fw-medium text-heading">'.e($row->description).'</span>';
            })
            ->addColumn('causer_name', function ($row) {
                if (! $row->causer) {
                    return '<span class="badge bg-label-secondary">النظام الآلي</span>';
                }

                return '<div class="d-flex align-items-center gap-1"><i class="ti ti-user text-muted"></i><span>'.e($row->causer->name ?? 'مستخدم #'.$row->causer_id).'</span></div>';
            })
            ->addColumn('subject_display', function ($row) {
                return '<span class="font-monospace text-muted">'.e($this->activitySubjectDisplay($row)).'</span>';
            })
            ->editColumn('created_at', function ($row) {
                if (! $row->created_at) {
                    return '-';
                }

                return sprintf(
                    '<span title="%s">%s</span>',
                    e($row->created_at->format('Y-m-d H:i:s')),
                    e($row->created_at->locale('ar')->diffForHumans())
                );
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'log_name', 'description', 'causer_name', 'subject_display', 'created_at', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['description', 'log_name'];
    }

    protected function getCustomOrder(): array
    {
        return [5, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [1, 2, 3, 4, 5],
            ]],
        ];

        if (auth('admin')->user()?->can('activity_log.view')) {
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
}
