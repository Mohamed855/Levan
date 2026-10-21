<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Tickets;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Tickets\TicketStatus;
use App\Services\Tenants\Shared\CentralTicketGateway;

class TenantTicketDataTable extends AbstractDataTable
{
    private string $page = 'tenant.tickets';

    private string $route = 'tenant.tickets';

    protected function getTableId(): string
    {
        return 'tenant-tickets-table';
    }

    protected function resource()
    {
        return (new CentralTicketGateway())->ticketsQueryForCurrentTenant();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'ticket_number', 'name' => 'ticket_number', 'title' => 'رقم التذكرة', 'className' => 'text-start'],
            ['data' => 'subject', 'name' => 'subject', 'title' => 'الموضوع', 'className' => 'text-start'],
            ['data' => 'department', 'name' => 'department', 'title' => 'القسم', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الفتح', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('ticket_number', function ($row) {
                $url = route($this->route.'.show', $row->id);
                return '<a href="'.$url.'" class="fw-bold text-primary font-monospace">'.e($row->ticket_number).'</a>';
            })
            ->editColumn('subject', fn ($row) => '<span class="fw-semibold">'.e($row->subject).'</span>')
            ->editColumn('department', function ($row) {
                $deptLabels = [
                    'technical' => 'الدعم الفني',
                    'billing' => 'الفواتير والمالية',
                    'general' => 'استفسارات عامة',
                    'auctions' => 'إدارة المزادات',
                ];
                $label = $deptLabels[$row->department] ?? ($row->department ?? 'عام');
                return '<span class="badge bg-label-info">'.e($label).'</span>';
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

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'ticket_number', 'subject', 'department', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'subject'];
    }

    protected function getCustomOrder(): array
    {
        return [5, 'desc'];
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                    'columns' => [1, 2, 3, 4, 5],
                ]],
            ],
        ];
    }

    private function getAddButton(): array
    {
        if (! auth('tenant')->user()?->can('tickets.create')) {
            return [];
        }

        return [
            'text' => '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">فتح تذكرة جديدة</span>',
            'className' => 'btn btn-primary btn-add',
            'action' => 'function() { window.location.href = "'.route($this->route.'.create').'"; }',
        ];
    }

    public function getStats(): array
    {
        return (new CentralTicketGateway())->statsForCurrentTenant();
    }
}
