<?php

namespace App\DataTables\Tenants\Tickets;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Tenants\Tickets\TicketStatus;
use App\Services\Shared\Integration\EtmamTicketService;
use Carbon\Carbon;

class TicketsDataTable extends AbstractDataTable
{
    private $route = 'tickets';

    private EtmamTicketService $ticketService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketService = new EtmamTicketService;
    }

    protected function getTableId()
    {
        return 'tickets-table';
    }

    protected function resource()
    {
        $status = request('status');
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }

        $result = $this->ticketService->list(
            page: 1,
            perPage: 1000,
            filters: $filters
        );

        if (! $result['success']) {
            return collect([]);
        }

        $tickets = $result['data']['data']['data'] ?? $result['data']['data'] ?? [];

        $collection = collect($tickets)->map(function ($ticket) {
            return (object) $ticket;
        });

        if ($status) {
            $collection = $collection->where('status', $status);
        }

        return $collection;
    }

    public function dataTable($query)
    {
        $datatable = datatables()
            ->collection($query);

        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'ticket_number',         'title' => 'رقم التذكرة', 'className' => 'text-start'],
            ['data' => 'title',                 'title' => 'العنوان', 'className' => 'text-start'],
            ['data' => 'ticketing_sections_id', 'title' => 'التصنيف', 'className' => 'text-start'],
            ['data' => 'status',                'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at',            'title' => 'تاريخ الإنشاء', 'className' => 'text-center'],
            ['data' => 'actions',               'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('ticket_number', function ($row) {
                return $this->copyableCode('#'.e($row->ticket_number));
            })

            ->editColumn('status', function ($row) {
                return TicketStatus::toBadge($row->status);
            })

            ->editColumn('ticketing_sections_id', function ($row) {
                return $this->getSectionName($row->ticketing_sections_id);
            })

            ->editColumn('created_at', function ($row) {
                try {
                    return Carbon::parse($row->created_at)->format('Y-m-d H:i');
                } catch (\Exception $e) {
                    return $row->created_at ?? '-';
                }
            })

            ->addColumn('actions', function ($row) {
                return view('tenant.tickets.action', [
                    'row' => $row,
                    'route' => $this->route,
                ])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['ticket_number', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'title'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
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
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }

    private function getAddButton(): array
    {
        if (! $this->ticketService->isServiceEnabled()) {
            return [
                'text' => '<i class="fas fa-ban me-1"></i> إضافة تذكرة (الخدمة متوقفة)',
                'className' => 'btn btn-secondary btn-add disabled',
                'action' => "function(){ if(typeof toastr !== 'undefined'){ toastr.warning('عذراً، خدمة فتح تذاكر جديدة متوقفة حالياً من قبل الإدارة.'); } else { alert('عذراً، خدمة فتح تذاكر جديدة متوقفة حالياً من قبل الإدارة.'); } }",
            ];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة تذكرة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }

    private function getSectionName($sectionId): string
    {
        static $sections = null;

        if ($sections === null) {
            $result = $this->ticketService->getSections();
            $sections = $result['success']
                ? collect($result['data']['data'] ?? [])->keyBy('id')
                : collect([]);
        }

        $section = $sections->get($sectionId);

        return $section['name'] ?? "قسم #{$sectionId}";
    }
}
