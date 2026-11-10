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
            ['data' => 'ticket_number', 'name' => 'ticket_number', 'title' => 'رقم التذكرة', 'className' => 'text-center'],
            ['data' => 'tenant_name',   'name' => 'tenant_name',   'title' => 'المستأجر', 'className' => 'text-center'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'صاحب التذكرة', 'className' => 'text-center'],
            ['data' => 'title',         'name' => 'title',         'title' => 'العنوان', 'className' => 'text-center'],
            ['data' => 'section',       'name' => 'section',       'title' => 'القسم', 'className' => 'text-center'],
            ['data' => 'status',        'name' => 'status',        'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at',    'name' => 'created_at',    'title' => 'تاريخ الإنشاء', 'className' => 'text-center'],
            ['data' => 'actions',       'name' => 'actions',       'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('ticket_number', function ($row) {
                return $this->copyableCode('#'.e($row->ticket_number));
            })
            ->addColumn('tenant_name', function ($row) {
                $tenant = $row->tenant;
                if (! $tenant) {
                    return '<span class="badge bg-secondary">'.e($row->tenant_id ?? 'عام').'</span>';
                }

                return '<span class="badge bg-info">'.e($tenant->company_name ?? $tenant->id).'</span>';
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
                return $this->formatDate($row->created_at);
            })
            ->addColumn('actions', function ($row) {
                return '<a href="'.route($this->route.'.show', $row->ticket_number).'" class="btn btn-icon btn-primary bg-primary" title="عرض التذكرة"><i class="ti ti-eye"></i></a>';
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
        return ['ticket_number', 'tenant_name', 'customer_name', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'title', 'customer_name', 'customer_email', 'tenant_id'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
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
        $ticketsEnabled = (bool) ($settings?->tickets_enabled ?? true);

        $icon = $ticketsEnabled ? 'ti-toggle-right' : 'ti-toggle-left';
        $text = $ticketsEnabled ? 'الخدمة مفعلة' : 'الخدمة غير مفعلة';
        $btnClass = $ticketsEnabled ? 'btn-success' : 'btn-danger';

        $confirmTitle = $ticketsEnabled ? 'تأكيد إيقاف خدمة التذاكر' : 'تأكيد تفعيل خدمة التذاكر';
        $confirmText = $ticketsEnabled
            ? 'هل أنت متأكد من إيقاف خدمة التذاكر في النظام؟'
            : 'هل أنت متأكد من تفعيل خدمة التذاكر في النظام؟';
        $confirmBtnText = $ticketsEnabled ? 'نعم، إيقاف الخدمة' : 'نعم، تفعيل الخدمة';
        $confirmVariant = $ticketsEnabled ? 'danger' : 'success';
        $confirmBtnClass = $ticketsEnabled ? 'btn btn-danger px-4' : 'btn btn-success px-4';

        return [
            'text' => '<i class="ti '.$icon.' me-1"></i> '.$text,
            'className' => 'btn '.$btnClass.' btn-add btn-toggle-service',
            'action' => "function() {
                var form = document.getElementById('toggle-service-form');
                if (!form) return;
                window.confirmAction({
                    title: '{$confirmTitle}',
                    text: '{$confirmText}',
                    variant: '{$confirmVariant}',
                    icon: 'ti-settings',
                    confirmText: '{$confirmBtnText}',
                    confirmClass: '{$confirmBtnClass}',
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
                'columns' => [7, 6, 5, 4, 3, 2, 1, 0],
            ]],
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}
