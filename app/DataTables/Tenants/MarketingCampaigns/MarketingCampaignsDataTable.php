<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\MarketingCampaigns;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;

final class MarketingCampaignsDataTable extends AbstractDataTable
{
    protected function buttons(): array
    {
        $buttons = [['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']]]];
        if (auth('tenant')->user()?->can('marketing_campaigns.create')) {
            $buttons[] = ['text' => '<i class="ti ti-plus me-1"></i> إضافة حملة', 'className' => 'btn btn-primary btn-add', 'action' => "function(){ window.location.href='".tenant_route_name('marketing-campaigns.create')."'; }"];
        }

        return $buttons;
    }

    protected function getTableId()
    {
        return 'marketing-campaigns-table';
    }

    protected function resource()
    {
        return MarketingCampaign::query()->with(['auction' => fn ($query) => $query->select('id', 'name')->withExists(['closing as is_finalized' => fn ($closing) => $closing->whereNotNull('finalized_snapshot')])])->withCount('evidence')->select('auction_marketing_campaigns.*');
    }

    protected function getColumns(): array
    {
        return [['data' => 'id', 'name' => 'id', 'title' => '#'], ['data' => 'auction_name', 'name' => 'auction.name', 'title' => 'المزاد'], ['data' => 'channel_label', 'name' => 'channel', 'title' => 'نوع الحملة'], ['data' => 'evidence_count', 'name' => 'evidence_count', 'title' => 'الأدلة', 'searchable' => false], ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'], ['data' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false]];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable->addColumn('auction_name', fn ($row) => $row->auction?->name ?? '-')->addColumn('channel_label', fn ($row) => $row->platform_label ?: __('auction_reports.channels.'.$row->channel))->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d'))->addColumn('actions', fn ($row) => view('tenant.marketing-campaigns.action', ['campaign' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['actions'];
    }

    protected function filterableColumns(): array
    {
        return ['channel', 'platform_label', 'auction.name'];
    }

    protected function applyCustomFilters($query)
    {
        if (request()->filled('channel')) {
            $query->where('channel', request('channel'));
        } if (request()->filled('auction_id')) {
            $query->where('auction_id', (int) request('auction_id'));
        }

        return $query;
    }
}
