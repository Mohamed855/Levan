<?php

namespace App\DataTables\Admin\Tickets;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Tenants\Tickets\TicketStatus;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tickets\Ticket;

class AdminTicketsDataTable extends AbstractDataTable
{
    private $page = 'admin.tickets';

    private $route = 'admin.tickets';

    protected function getTableId()
    {
        return 'admin-tickets-table';
    }

    protected function resource()
    {
        return Ticket::with(['tenant', 'section'])->latest();
    }

    protected function getColumns(): array
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'width' => '10px',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'DT_RowIndex',   'name' => 'DT_RowIndex',   'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '30px'],
            ['data' => 'ticket_number', 'name' => 'ticket_number', 'title' => 'رقم التذكرة'],
            ['data' => 'tenant_name',   'name' => 'tenant_name',   'title' => 'المستأجر'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'صاحب التذكرة'],
            ['data' => 'title',         'name' => 'title',         'title' => 'العنوان'],
            ['data' => 'section',       'name' => 'section',       'title' => 'القسم'],
            ['data' => 'status',        'name' => 'status',        'title' => 'الحالة'],
            ['data' => 'created_at',    'name' => 'created_at',    'title' => 'تاريخ الإنشاء'],
            ['data' => 'actions',       'name' => 'actions',       'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('ticket_number', function ($row) {
                return $this->copyableCode('#'.e($row->ticket_number));
            })
            ->addColumn('tenant_name', function ($row) {
                $tenant = $row->tenant;
                if (! $tenant) {
                    return '<span class="badge bg-secondary">'.e($row->tenant_id ?? 'عام').'</span>';
                }

                return '<span class="badge bg-label-info">'.e($tenant->company_name ?? $tenant->id).'</span>';
            })
            ->editColumn('customer_name', function ($row) {
                return '<div><div class="fw-semibold">'.e($row->customer_name ?? '-').'</div>'.
                       ($row->customer_email ? '<small class="text-muted">'.e($row->customer_email).'</small>' : '').
                       '</div>';
            })
            ->addColumn('section', function ($row) {
                return e($row->section?->name ?? 'الدعم الفني');
            })
            ->editColumn('status', function ($row) {
                return TicketStatus::toBadge($row->status);
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return '<a href="'.route($this->route.'.show', $row->ticket_number).'" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض التذكرة"><i class="ti ti-eye"></i></a>';
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('section')) {
            $query->where('ticketing_sections_id', $request->section);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'ticket_number', 'tenant_name', 'customer_name', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'title', 'customer_name', 'customer_email', 'tenant_id'];
    }

    protected function getCustomOrder(): array
    {
        return [2, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getToggleServiceButton(),
        ];
    }

    private function getToggleServiceButton(): array
    {
        $settings = Settings::first();
        $ticketsEnabled = $settings?->tickets_enabled ?? true;

        $icon = $ticketsEnabled ? 'ti-toggle-right' : 'ti-toggle-left';
        $text = $ticketsEnabled ? 'الخدمة مفعلة' : 'الخدمة معطلة';
        $btnClass = $ticketsEnabled ? 'btn-success' : 'btn-danger';

        return [
            'text' => '<i class="ti '.$icon.' me-1"></i> '.$text,
            'className' => 'btn '.$btnClass.' btn-add',
            'action' => "function() {
                var form = document.getElementById('toggle-service-form');
                if (!form) return;
                window.confirmAction({
                    title: 'تأكيد تغيير حالة الخدمة',
                    text: 'هل أنت متأكد من تغيير حالة خدمة التذاكر في النظام؟',
                    variant: 'warning',
                    icon: 'ti-settings',
                    confirmText: 'نعم، تغيير الحالة',
                    confirmClass: 'btn btn-primary px-4',
                    onConfirm: function() {
                        form.submit();
                    }
                });
            }",
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [10, 9, 8, 7, 6, 5, 4, 3],
            ]],
        ];

        if (auth()->user()?->can('tickets.delete') || auth()->user()?->hasRole('Super Admin')) {
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
