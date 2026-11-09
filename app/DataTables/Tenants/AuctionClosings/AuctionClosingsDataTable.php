<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\AuctionClosings;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;

final class AuctionClosingsDataTable extends AbstractDataTable
{
    private string $route = 'auction-closings';

    protected function getTableId(): string
    {
        return 'auction-closings-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->with([
                'assignment:id,assignment_number',
                'closing' => fn ($q) => $q->select(['id', 'auction_id', 'saved_at'])
                    ->selectRaw('finalized_snapshot IS NOT NULL as is_finalized'),
            ])
            ->withCount('properties');
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
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            [
                'data' => 'id',
                'name' => 'id',
                'title' => '#',
                'width' => '60px',
                'className' => 'text-center',
            ],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => 'المزاد',
                'width' => '250px',
            ],
            [
                'data' => 'assignment_number',
                'name' => 'assignment.assignment_number',
                'title' => 'رقم الإسناد',
                'orderable' => false,
                'className' => 'text-center',
                'width' => '140px',
            ],
            [
                'data' => 'properties_count',
                'name' => 'properties_count',
                'title' => 'عدد العقارات',
                'searchable' => false,
                'className' => 'text-center',
                'width' => '110px',
            ],
            [
                'data' => 'closing_status',
                'title' => 'الإقفال',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '130px',
            ],
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'className' => 'text-center',
            ],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('name', function ($row) {
                $url = tenant_route_name('auctions.show', $row->id);
                $name = e($row->name);

                return '<a href="'.$url.'" class="fw-semibold text-heading">'.$name.'</a>';
            })
            ->addColumn('assignment_number', fn ($row) => $row->assignment?->assignment_number ?? '-')
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-label-secondary">'.(int) $row->properties_count.'</span>';
            })
            ->addColumn('closing_status', function ($row) {
                if ($row->closing?->is_finalized) {
                    return '<span class="badge bg-label-success">منتهي</span>';
                }
                if ($row->closing) {
                    return '<span class="badge bg-label-info">محفوظ</span>';
                }

                return '<span class="badge bg-label-warning">لم يبدأ</span>';
            })
            ->addColumn('actions', fn ($row) => view('tenant.auction-closings.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'properties_count', 'closing_status', 'actions'];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city', 'assignment.assignment_number'];
    }

    protected function applyCustomFilters($query)
    {
        if (request('closing_status') === 'ended') {
            $query->whereHas('closing', fn ($q) => $q->whereNotNull('finalized_snapshot'));
        } elseif (request('closing_status') === 'not_started') {
            $query->doesntHave('closing');
        } elseif (request('closing_status') === 'saved') {
            $query->whereHas('closing', fn ($q) => $q->whereNull('finalized_snapshot'));
        }

        return $query;
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
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }
}
