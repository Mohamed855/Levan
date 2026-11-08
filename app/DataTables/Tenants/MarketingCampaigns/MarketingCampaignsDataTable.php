<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\MarketingCampaigns;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;

final class MarketingCampaignsDataTable extends AbstractDataTable
{
    protected function buttons(): array
    {
        $buttons = [
            ['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']]],
        ];

        if (auth('tenant')->user()?->can('marketing_campaigns.create')) {
            $buttons[] = [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة حملات لمزاد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name('marketing-campaigns.create')."'; }",
            ];
        }

        return $buttons;
    }

    protected function getTableId()
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
            ->withCount('marketingCampaigns')
            ->withExists(['closing as is_finalized' => fn ($closing) => $closing->whereNotNull('finalized_snapshot')])
            ->select('auctions.*');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => '#'],
            ['data' => 'name', 'name' => 'name', 'title' => 'المزاد'],
            ['data' => 'marketing_campaigns_count', 'name' => 'marketing_campaigns_count', 'title' => 'عدد الحملات', 'searchable' => false],
            ['data' => 'channels', 'name' => 'channels', 'title' => 'أنواع الحملات', 'orderable' => false, 'searchable' => false],
            ['data' => 'evidence_count', 'name' => 'evidence_count', 'title' => 'الأدلة المرفقة', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ المزاد'],
            ['data' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->editColumn('name', function ($row) {
                $url = tenant_route_name('auctions.show', $row->id);
                $name = e($row->name);
                $assign = $row->assignment?->assignment_number ? '<small class="text-muted d-block">إسناد: '.e($row->assignment->assignment_number).'</small>' : '';

                return '<a href="'.$url.'" class="fw-semibold text-heading">'.$name.'</a>'.$assign;
            })
            ->addColumn('channels', function ($row) {
                $labels = __('auction_reports.channels', [], 'ar') ?: [];
                $badges = [];
                $campaigns = $row->marketingCampaigns ?? collect();
                foreach ($campaigns as $campaign) {
                    $label = $campaign->platform_label ?: ($labels[$campaign->channel] ?? $campaign->channel);
                    $badges[] = '<span class="badge bg-label-primary m-1">'.e((string) $label).'</span>';
                }

                return implode(' ', $badges) ?: '-';
            })
            ->addColumn('evidence_count', function ($row) {
                $total = $row->marketingCampaigns->sum('evidence_count');

                return '<span class="badge bg-label-info">'.$total.' دليل</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d'))
            ->addColumn('actions', fn ($row) => view('tenant.marketing-campaigns.action', ['auction' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['name', 'channels', 'evidence_count', 'actions'];
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
}
