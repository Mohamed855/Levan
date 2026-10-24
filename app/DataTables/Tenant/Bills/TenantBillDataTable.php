<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Bills;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Carbon\Carbon;

class TenantBillDataTable extends AbstractDataTable
{
    private string $page = 'tenant.bills';

    private string $route = 'tenant.bills';

    protected function getTableId(): string
    {
        return 'tenant-bills-table';
    }

    protected function resource()
    {
        return (new CentralBillingGateway())->billsQueryForCurrentTenant();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'bill_number', 'name' => 'bill_number', 'title' => 'رقم الفاتورة'],
            ['data' => 'type', 'name' => 'type', 'title' => 'نوع العملية', 'className' => 'text-center'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'المبلغ', 'className' => 'text-center'],
            ['data' => 'settled_amount', 'name' => 'settled_amount', 'title' => 'التسوية', 'className' => 'text-center'],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => 'طريقة الدفع', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة العملية', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ العملية', 'className' => 'text-center'],
            ['data' => 'due_at', 'name' => 'due_at', 'title' => 'آخر تاريخ للسداد', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('bill_number', function ($row) {
                $url = route($this->route.'.show', $row->id);
                return '<a href="'.$url.'" class="fw-bold text-primary font-monospace">'.e($row->bill_number).'</a>';
            })
            ->editColumn('type', function ($row) {
                $type = $row->type instanceof BillType ? $row->type : BillType::tryFrom((string) $row->type);
                $color = $type ? $type->color() : 'secondary';
                $label = $type ? $type->label() : (string) $row->type;
                return '<span class="badge bg-label-'.$color.'">'.e($label).'</span>';
            })
            ->editColumn('amount', fn ($row) => number_format((float) $row->amount, 2).' ر.س')
            ->editColumn('settled_amount', fn ($row) => $row->settled_amount ? number_format((float) $row->settled_amount, 2).' ر.س' : '-')
            ->editColumn('payment_method', function ($row) {
                if (! $row->payment_method) {
                    return '<span class="text-muted">-</span>';
                }
                $method = $row->payment_method instanceof PaymentMethod ? $row->payment_method : PaymentMethod::tryFrom((string) $row->payment_method);
                return $method ? $method->badge() : '<span class="badge bg-label-info">'.e((string) $row->payment_method).'</span>';
            })
            ->editColumn('status', function ($row) {
                $status = $row->status instanceof BillStatus ? $row->status : BillStatus::tryFrom((string) $row->status);
                $color = $status ? $status->color() : 'secondary';
                $label = $status ? $status->label() : (string) $row->status;
                return '<span class="badge bg-'.$color.'">'.e($label).'</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d') ?? '-')
            ->editColumn('due_at', function ($row) {
                if (! $row->due_at) {
                    return '<span class="text-muted">-</span>';
                }

                $statusVal = $row->status instanceof BillStatus ? $row->status->value : (string) $row->status;
                $isOverdue = Carbon::parse($row->due_at)->isPast() && $statusVal !== 'paid';

                return sprintf(
                    '<span class="%s">%s</span>',
                    $isOverdue ? 'text-danger fw-bold' : '',
                    $row->due_at->format('Y-m-d')
                );
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'bill_number', 'type', 'payment_method', 'status', 'due_at', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['bill_number'];
    }

    protected function getCustomOrder(): array
    {
        return [7, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
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
                    'columns' => [1, 2, 3, 4, 5, 6, 7, 8],
                ]],
            ],
        ];
    }

    public function getStats(): array
    {
        return (new CentralBillingGateway())->statsForCurrentTenant();
    }
}
