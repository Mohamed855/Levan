<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Tickets;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;

class TicketDataTable extends AbstractDataTable
{
    private string $page = 'admin.tickets';

    private string $route = 'admin.tickets';

    protected function getTableId(): string
    {
        return 'tickets-table';
    }

    protected function resource()
    {
        return Ticket::query()->with('tenant');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'ticket_number', 'name' => 'ticket_number', 'title' => 'رقم التذكرة', 'className' => 'text-start'],
            ['data' => 'tenant_name', 'name' => 'tenant.company_name', 'title' => 'المستأجر'],
            ['data' => 'subject', 'name' => 'subject', 'title' => 'الموضوع'],
            ['data' => 'department', 'name' => 'department', 'title' => 'القسم', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الفتح', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('ticket_number', function ($row) {
                $url = route($this->route.'.show', $row->id);
                return '<a href="'.$url.'" class="fw-bold text-primary font-monospace">#'.e($row->ticket_number).'</a>';
            })
            ->editColumn('tenant_name', function ($row) {
                $tenant = $row->tenant;
                if (! $tenant) {
                    return '<span class="badge bg-label-secondary">عام / منصة</span>';
                }
                return '<span class="badge bg-label-info">'.e($tenant->company_name ?? $tenant->id).'</span>';
            })
            ->editColumn('subject', function ($row) {
                $desc = $row->description ? '<small class="text-muted d-block text-truncate" style="max-width: 280px;">'.e($row->description).'</small>' : '';
                return '<div><span class="fw-medium">'.e($row->subject).'</span>'.$desc.'</div>';
            })
            ->editColumn('department', function ($row) {
                return '<span class="badge bg-label-secondary">'.e($row->department ?: 'الدعم الفني').'</span>';
            })
            ->editColumn('status', function ($row) {
                $status = $row->status instanceof TicketStatus ? $row->status : TicketStatus::tryFrom((string) $row->status);
                $color = $status ? $status->color() : 'secondary';
                $label = $status ? $status->label() : (string) $row->status;
                return '<span class="badge bg-'.$color.'">'.e($label).'</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'ticket_number', 'tenant_name', 'subject', 'department', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'subject', 'description'];
    }

    protected function getCustomOrder(): array
    {
        return [6, 'desc'];
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
                'columns' => [1, 2, 3, 4, 5, 6],
            ]],
        ];

        if (auth('admin')->user()?->can('tickets.update')) {
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
