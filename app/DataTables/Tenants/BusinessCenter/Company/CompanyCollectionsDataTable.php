<?php

namespace App\DataTables\Tenants\BusinessCenter\Company;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\FinancialCollection\Collection\Collection;

class CompanyCollectionsDataTable extends AbstractDataTable
{
    private $companyId;
    private $route = "reports.companies";  

    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
        return $this;
    }

    protected function getTableId()
    {
        return "company-collections-table";
    }
 
    // ✅ Resource Query
  protected function resource()
{
    return Collection::query()
        ->select([
            'collections.id',
            'collections.customer_id',
            'collections.collected_amount',
            'collections.collection_type',
            'collections.payment_type',
            'collections.receipt_number',
            'collections.collection_date',
            'collections.status',
            'collections.payment_method_id',
            'customers.name as customer_name'
        ])
        ->join('customers', 'collections.customer_id', '=', 'customers.id')
        // ✅ بس ضيف paymentMethod، مش customers
        ->with(['paymentMethod:id,name'])
        ->where('customers.company_id', $this->companyId);
}

    // ✅ Columns
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'collections.id', 'title' => 'ID', 'visible' => false],
            ['data' => 'customer_name', 'name' => 'customers.name', 'title' => 'العميل', 'className' => 'fw-medium'],
            ['data' => 'collected_amount', 'name' => 'collections.collected_amount', 'title' => 'المبلغ'],
            ['data' => 'collection_type', 'name' => 'collections.collection_type', 'title' => 'نوع التحصيل'],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => 'طريقة الدفع', 'orderable' => false, 'searchable' => false],
            ['data' => 'receipt_number', 'name' => 'collections.receipt_number', 'title' => 'رقم الإيصال'],
            ['data' => 'collection_date', 'name' => 'collections.collection_date', 'title' => 'تاريخ التحصيل'],
            ['data' => 'payment_type', 'name' => 'collections.payment_type', 'title' => 'نوع السداد'],
            ['data' => 'status', 'name' => 'collections.status', 'title' => 'الحالة'],
        ];
    }

    // ✅ Custom Columns
    protected function addCustomColumns($datatable)
    {
        $datatable
            // ✅ Row Index
            ->addIndexColumn()

            // Customer name
            ->editColumn('customer_name', function ($collection) {
                return '<div class="d-flex align-items-center">
                            <i class="ti ti-user text-success me-1"></i>
                            <span class="fw-medium">' . e($collection->customer_name ?? '—') . '</span>
                        </div>';
            })

            // Amount
            ->editColumn('collected_amount', function ($collection) {
                return '<span class="fw-semibold text-primary">' . 
                       number_format($collection->collected_amount, 2) . 
                       '</span>';
            })

            // Collection type
            ->editColumn('collection_type', function ($collection) {
                return $this->collectionTypeBadge($collection);
            })

            // Payment method
            ->addColumn('payment_method', function ($collection) {
                return '<span class="text-muted">' . 
                       e($collection->paymentMethod?->name ?? '—') . 
                       '</span>';
            })

            // Receipt number
            ->editColumn('receipt_number', function ($collection) {
                return '<code class="bg-light px-2 py-1 rounded">' . 
                       e($collection->receipt_number ?? '—') . 
                       '</code>';
            })

            // Collection date
            ->editColumn('collection_date', function ($collection) {
                return $collection->collection_date 
                    ? '<span class="text-muted">' . \Carbon\Carbon::parse($collection->collection_date)->format('Y-m-d') . '</span>'
                    : '<span class="text-muted">—</span>';
            })

            // Payment type
            ->editColumn('payment_type', function ($collection) {
                return $this->paymentTypeBadge($collection);
            })

            // Status
            ->editColumn('status', function ($collection) {
                return $this->collectionStatusBadge($collection);
            });
    }

    // ✅ Raw Columns
    protected function rawColumns(): array
    {
        return [
            'customer_name', 
            'collected_amount', 
            'collection_type', 
            'payment_method', 
            'receipt_number', 
            'collection_date', 
            'payment_type', 
            'status'
        ];
    }

     protected function getSearchableColumns(): array
    {
        return [
            'customer_name',
        ];
    }

    // ✅ Custom Filters
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('collections.status', $request->status);
        }

        if ($request->filled('collection_type')) {
            $query->where('collections.collection_type', $request->collection_type);
        }

        if ($request->filled('payment_type')) {
            $query->where('collections.payment_type', $request->payment_type);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('collections.collection_date', [$request->date_from, $request->date_to]);
        }

        return $query;
    }

    // ✅ Custom Order
    protected function getCustomOrder(): array
    {
        return [7, 'desc']; // collection_date
    }

    // ✅ Buttons
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
            'className' => 'btn btn-sm btn-label-secondary dropdown-toggle',
            'text' => '<i class="ti ti-download me-1"></i>تصدير',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل', 'title' => 'تحصيلات الشركة'],
                ['extend' => 'pdf', 'text' => 'PDF', 'title' => 'تحصيلات الشركة'],
            ],
        ];
    }

    /*
    |============================================================================
    |                       Private Helper Methods
    |============================================================================
    */
    private function collectionTypeBadge($collection): string
    {
        $types = [
            'cash' => ['class' => 'success', 'label' => 'نقدي', 'icon' => 'cash'],
            'scheduled' => ['class' => 'info', 'label' => 'مجدول', 'icon' => 'calendar-event']
        ];

        $config = $types[$collection->collection_type] ?? ['class' => 'secondary', 'label' => $collection->collection_type, 'icon' => 'help'];

        return sprintf(
            '<span class="badge bg-label-%s"><i class="ti ti-%s me-1"></i>%s</span>',
            $config['class'],
            $config['icon'],
            $config['label']
        );
    }

    private function paymentTypeBadge($collection): string
    {
        $types = [
            'settlement' => ['class' => 'primary', 'label' => 'تسوية'],
            'without_settlement' => ['class' => 'secondary', 'label' => 'محول']
        ];

        $config = $types[$collection->payment_type] ?? ['class' => 'secondary', 'label' => $collection->payment_type];

        return sprintf(
            '<span class="badge bg-label-%s">%s</span>',
            $config['class'],
            $config['label']
        );
    }

    private function collectionStatusBadge($collection): string
    {
        $statuses = [
            'approved' => ['class' => 'success', 'label' => 'معتمد'],
            'pending' => ['class' => 'warning', 'label' => 'معلق'],
            'rejected' => ['class' => 'danger', 'label' => 'مرفوض']
        ];

        $config = $statuses[$collection->status] ?? ['class' => 'secondary', 'label' => $collection->status];

        return sprintf(
            '<span class="badge bg-label-%s">%s</span>',
            $config['class'],
            $config['label']
        );
    }
}