<?php

declare(strict_types=1);

namespace App\DataTables\Admin\PublicRequests;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\PublicRequests\TrialRequest;

class TrialRequestsDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'trial-requests-table';
    }

    protected function resource()
    {
        return TrialRequest::query()->latest();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'width' => '50px'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'الاسم'],
            ['data' => 'customer_email', 'name' => 'customer_email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'customer_phone', 'name' => 'customer_phone', 'title' => 'الهاتف'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإرسال', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '140px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('status', function ($row) {
                $status = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;
                $color = match ($status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'secondary',
                };
                $label = match ($status) {
                    'pending' => 'قيد الانتظار',
                    'approved' => 'تمت الموافقة',
                    'rejected' => 'مرفوض',
                    default => $status,
                };
                return '<span class="badge bg-label-'.$color.'">'.e($label).'</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                $html = '<div class="d-flex justify-content-center gap-1">';
                $status = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;

                if ($status === 'pending' && auth('admin')->user()?->can('public_requests.process')) {
                    $html .= '<form method="POST" action="'.route('admin.trial-requests.approve', $row).'" class="d-inline">'.csrf_field().'<button type="submit" class="btn btn-sm btn-icon btn-label-success bg-label-success" title="موافقة وإنشاء حساب"><i class="ti ti-check"></i></button></form>';
                    $html .= '<form method="POST" action="'.route('admin.trial-requests.reject', $row).'" class="d-inline">'.csrf_field().'<button type="submit" class="btn btn-sm btn-icon btn-label-danger bg-label-danger" title="رفض الطلب" onclick="return confirm(\'هل أنت متأكد من رفض الطلب؟\')"><i class="ti ti-x"></i></button></form>';
                }

                $html .= '</div>';
                return $html;
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return ['customer_name', 'customer_email', 'customer_phone'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
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
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('admin')->user()?->can('public_requests.process')) {
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
