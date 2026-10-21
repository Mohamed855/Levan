<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Notifications;

use App\DataTables\Abstract\AbstractDataTable;

class TenantNotificationDataTable extends AbstractDataTable
{
    private string $page = 'tenant.notifications';

    private string $route = 'tenant.notifications';

    protected function getTableId(): string
    {
        return 'tenant-notifications-table';
    }

    protected function resource()
    {
        $user = auth('tenant')->user();

        return $user ? $user->notifications()->latest() : \Illuminate\Notifications\DatabaseNotification::query()->whereRaw('1=0');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'title', 'name' => 'data->title', 'title' => 'الإشعار', 'className' => 'text-start'],
            ['data' => 'message', 'name' => 'data->message', 'title' => 'الرسالة', 'className' => 'text-start'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ والوقت', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'read_at', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('title', function ($row) {
                $title = $row->data['title'] ?? 'إشعار من النظام';
                $isUnread = $row->read_at === null;
                $bold = $isUnread ? 'fw-bold text-dark' : 'text-muted';
                return sprintf(
                    '<div class="d-flex align-items-center gap-2"><i class="ti ti-bell %s"></i><span class="%s">%s</span></div>',
                    $isUnread ? 'text-primary' : 'text-muted',
                    $bold,
                    e($title)
                );
            })
            ->editColumn('message', function ($row) {
                $message = $row->data['message'] ?? ($row->data['body'] ?? '-');
                return '<span class="text-wrap">'.e($message).'</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->diffForHumans() ?? '-')
            ->editColumn('status', function ($row) {
                if ($row->read_at === null) {
                    return '<span class="badge bg-label-warning">غير مقروء</span>';
                }
                return '<span class="badge bg-label-success">مقروء</span>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'id' => $row->id])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'title', 'message', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['data'];
    }

    protected function getCustomOrder(): array
    {
        return [3, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            [
                'text' => '<i class="ti ti-check-double me-1"></i> تحديد الكل كمقروء',
                'className' => 'btn btn-outline-primary',
                'action' => 'function() { window.markAllAsRead(); }',
            ],
            [
                'text' => '<i class="ti ti-trash me-1"></i> حذف المحدد',
                'className' => 'btn btn-label-danger btn-delete-selected',
                'action' => 'function() { window.handleNotificationMassDelete(); }',
            ],
        ];
    }

    public function getStats(): array
    {
        $user = auth('tenant')->user();
        if (! $user) {
            return ['total' => 0, 'unread' => 0, 'read' => 0, 'today' => 0, 'system' => 0];
        }

        $all = $user->notifications();

        return [
            'total' => (clone $all)->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => (clone $all)->whereNotNull('read_at')->count(),
            'today' => (clone $all)->whereDate('created_at', today())->count(),
            'system' => (clone $all)->count(),
        ];
    }
}
