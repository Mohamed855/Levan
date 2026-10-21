<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\ActivityLog;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\ActivityLog;

class TenantActivityLogDataTable extends AbstractDataTable
{
    private string $page = 'tenant.activity-log';

    private string $route = 'tenant.activity-log';

    protected function getTableId(): string
    {
        return 'tenant-activity-log-table';
    }

    protected function resource()
    {
        return ActivityLog::query()->with('causer')->latest();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'width' => '50px', 'className' => 'text-center'],
            ['data' => 'causer', 'name' => 'causer_id', 'title' => 'المستخدم', 'className' => 'text-start'],
            ['data' => 'event', 'name' => 'event', 'title' => 'نوع الحدث', 'className' => 'text-center'],
            ['data' => 'subject', 'name' => 'subject_type', 'title' => 'الكيان المتأثر', 'className' => 'text-start'],
            ['data' => 'description', 'name' => 'description', 'title' => 'التفاصيل', 'className' => 'text-start'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ والوقت', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '80px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->editColumn('id', fn ($row) => '<span class="font-monospace text-muted">#'.$row->id.'</span>')
            ->editColumn('causer', function ($row) {
                if (! $row->causer) {
                    return '<span class="text-muted">النظام التلقائي</span>';
                }
                return '<span class="fw-semibold">'.e($row->causer->name ?? 'مستخدم').'</span>';
            })
            ->editColumn('event', fn ($row) => $row->getEventBadge())
            ->editColumn('subject', function ($row) {
                if (! $row->subject_type) {
                    return '<span class="text-muted">-</span>';
                }
                $basename = class_basename($row->subject_type);
                $names = [
                    'Property' => 'عقار',
                    'Assignment' => 'إسناد',
                    'Auction' => 'مزاد',
                    'Company' => 'شركة',
                    'User' => 'مستخدم',
                    'Setting' => 'إعدادات',
                ];
                $label = $names[$basename] ?? $basename;
                return '<span class="badge bg-label-secondary">'.e($label).' #'.($row->subject_id ?? '').'</span>';
            })
            ->editColumn('description', fn ($row) => '<span class="text-wrap">'.e($row->description).'</span>')
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i:s') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['id', 'causer', 'event', 'subject', 'description', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['description', 'event'];
    }

    protected function getCustomOrder(): array
    {
        return [0, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => [
                    ['extend' => 'copy', 'text' => 'نسخ'],
                    ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                        'columns' => [0, 1, 2, 3, 4, 5],
                    ]],
                ],
            ],
        ];
    }

    public function getStats(): array
    {
        $base = ActivityLog::query();

        return [
            'total' => (clone $base)->count(),
            'created' => (clone $base)->where('event', 'created')->count(),
            'updated' => (clone $base)->where('event', 'updated')->count(),
            'deleted' => (clone $base)->where('event', 'deleted')->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
        ];
    }
}
