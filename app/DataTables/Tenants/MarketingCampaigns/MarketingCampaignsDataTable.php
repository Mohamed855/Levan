<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\MarketingCampaigns;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;

final class MarketingCampaignsDataTable extends AbstractDataTable
{
    private string $route = 'marketing-campaigns';

    protected function getTableId(): string
    {
        return 'marketing-campaigns-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->has('marketingCampaigns')
            ->with([
                'marketingCampaigns' => fn ($query) => $query->select('id', 'auction_id', 'channel', 'platform_label')->withCount('evidence'),
                'assignment:id,assignment_number',
            ])
            ->withCount(['marketingCampaigns as marketing_campaigns_count' => fn ($q) => $q->select(\Illuminate\Support\Facades\DB::raw('count(distinct channel)'))])
            ->withExists(['closing as is_finalized' => fn ($closing) => $closing->whereNotNull('finalized_snapshot')])
            ->select('auctions.*');
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
                'width' => '220px',
            ],
            [
                'data' => 'marketing_campaigns_count',
                'name' => 'marketing_campaigns_count',
                'title' => 'عدد الحملات',
                'searchable' => false,
                'className' => 'text-center',
                'width' => '110px',
            ],
            [
                'data' => 'evidence_count',
                'name' => 'evidence_count',
                'title' => 'مرفقات الحملات',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '120px',
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'تاريخ المزاد',
                'className' => 'text-center',
                'width' => '120px',
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
                if ($row->is_finalized || $row->isClosed()) {
                    return '<input type="checkbox" class="row-checkbox" disabled title="لا يمكن حذف حملات مزاد مقفل أو منتهٍ">';
                }

                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('name', function ($row) {
                $url = tenant_route_name('auctions.show', $row->id);
                $name = e($row->name);
                $assign = $row->assignment?->assignment_number ? '<small class="text-muted d-block">إسناد: '.e($row->assignment->assignment_number).'</small>' : '';

                return '<a href="'.$url.'" class="fw-semibold text-heading">'.$name.'</a>'.$assign;
            })
            ->editColumn('marketing_campaigns_count', function ($row) {
                $count = $row->marketingCampaigns ? $row->marketingCampaigns->pluck('channel')->filter()->unique()->count() : (int) ($row->marketing_campaigns_count ?? 0);

                return '<span class="badge bg-label-primary fs-6">'.$count.'</span>';
            })
            ->addColumn('evidence_count', function ($row) {
                $total = $row->marketingCampaigns->sum('evidence_count');

                return '<span class="badge bg-label-info">'.$total.' مرفق</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d'))
            ->addColumn('actions', fn ($row) => view('tenant.marketing-campaigns.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'marketing_campaigns_count', 'evidence_count', 'actions'];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city'];
    }

    protected function applyCustomFilters($query)
    {
        if (request()->filled('auction_id')) {
            $query->where('auctions.id', (int) request('auction_id'));
        }
        if (request()->filled('channel')) {
            $query->whereHas('marketingCampaigns', fn ($q) => $q->where('channel', request('channel')));
        }
        if (request()->filled('created_from')) {
            $query->whereDate('auctions.created_at', '>=', request('created_from'));
        }
        if (request()->filled('created_to')) {
            $query->whereDate('auctions.created_at', '<=', request('created_to'));
        }

        return $query;
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
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('marketing_campaigns.delete')) {
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

    private function getAddButton(): array
    {
        if (auth('tenant')->user()?->can('marketing_campaigns.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة حملات لمزاد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
