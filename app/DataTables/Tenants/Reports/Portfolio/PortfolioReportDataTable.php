<?php

namespace App\DataTables\Tenants\Reports\Portfolio;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Carbon\Carbon;

class PortfolioReportDataTable extends AbstractDataTable
{
    private $page = 'tenants.portfolio';

    private $route = 'portfolio';

    protected function getTableId()
    {
        return 'portfolio-table';
    }

    protected function resource()
    {
        return Portfolio::query();
    }

    protected function applyCustomFilters($query)
    {
        $request = request();
        if ($request->filled('date_from')) {
            $query->whereDate('assignment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('assignment_date', '<=', $request->date_to);
        }

        return $query;
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'visible' => false],
            [
                'data' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'width' => '10px',
            ],
            ['data' => 'company_name', 'name' => 'company.name', 'title' => 'اسم الشركة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المحفظة'],
            ['data' => 'assignment_date', 'name' => 'assignment_date', 'title' => 'تاريخ الإسناد'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('company_name', function (Portfolio $portfolio) {
                $companyName = $portfolio->company->name ?? 'غير محدد';

                return '<span class="text-primary">'.e($companyName).'</span>';
            })
            // ->editColumn('name', function (Portfolio $portfolio) {
            //     $url = tenant_route_name('portfolios.show', $portfolio->id);
            //     return '<a href="' . $url . '" class="text-body fw-medium">' . e($portfolio->name) . '</a>';
            // })
            ->editColumn('status', fn ($row) => $row->status == 'active' ? '<span class="badge bg-success">نشطة</span>' : '<span class="badge bg-secondary">غير نشطة</span>')
            ->editColumn('assignment_date', fn ($row) => $row->assignment_date ? Carbon::parse($row->assignment_date)->format('Y-m-d') : '<span class="text-muted">لم يتم الإسناد	</span>')

            ->editColumn('created_at', fn ($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->addColumn('action', fn ($row) => view('tenants.reports.portfolios.action', ['id' => $row->id, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'company_name', 'name', 'assignment_date', 'status', 'action'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'company.name'];
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

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    private function TypeBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->type->color(),
            $row->type->label()
        );
    }

    private function PaymentMethodBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->payment_method?->color(),
            $row->payment_method?->label()
        );
    }

    private function StatusBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->status->color(),
            $row->status->label()
        );
    }
}
