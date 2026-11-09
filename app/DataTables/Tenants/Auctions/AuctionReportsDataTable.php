<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use Illuminate\Support\Facades\DB;

final class AuctionReportsDataTable extends AbstractDataTable
{
    private string $route = 'auction-reports';

    protected function getTableId(): string
    {
        return 'auction-reports-table';
    }

    protected function resource()
    {
        $kinds = DB::query()->selectRaw("'outputs' as report_kind")->unionAll(DB::query()->selectRaw("'report' as report_kind"));

        return Auction::query()
            ->select(['auctions.*', 'report_kinds.report_kind'])
            ->crossJoinSub($kinds, 'report_kinds')
            ->with([
                'marketingCampaigns',
                'properties:id',
                'closing' => fn ($q) => $q->select(['id', 'auction_id'])->selectRaw('finalized_snapshot IS NOT NULL as is_finalized'),
                'publishedReportGeneration:id,status,outputs_path,report_path',
                'latestReportGeneration:auction_report_generations.id,auction_report_generations.auction_id,auction_report_generations.status',
            ]);
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
                'data' => 'name',
                'name' => 'auctions.name',
                'title' => 'المزاد',
                'width' => '250px',
            ],
            [
                'data' => 'kind_label',
                'name' => 'report_kind',
                'title' => 'التقرير',
                'className' => 'text-center',
                'width' => '140px',
            ],
            [
                'data' => 'content_status',
                'title' => 'حالة التقرير',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '130px',
            ],
            [
                'data' => 'generation_status',
                'title' => 'تجهيز الملف',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '140px',
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
            ->setRowId(fn ($row) => 'report-'.$row->id.'-'.$row->report_kind)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'-'.$row->report_kind.'">';
            })
            ->editColumn('name', function ($row) {
                $url = tenant_route_name('auctions.show', $row->id);
                $name = e($row->name);

                return '<a href="'.$url.'" class="fw-semibold text-heading">'.$name.'</a>';
            })
            ->addColumn('kind_label', fn ($row) => $row->report_kind === 'outputs' ? 'مخرجات المزاد' : 'التقرير النهائي')
            ->addColumn('content_status', function ($row) {
                $state = (new AuctionReportState)->forAuction($row);

                return '<span class="badge bg-label-'.$state->color().'">'.e($state->label()).'</span>';
            })
            ->addColumn('generation_status', fn ($row) => match ($row->latestReportGeneration?->status) {
                'ready' => '<span class="badge bg-label-success">جاهز</span>',
                'failed' => '<span class="badge bg-label-danger">تعذر التجهيز</span>',
                'queued', 'processing' => '<span class="badge bg-label-info"><i class="ti ti-loader ti-spin me-1"></i>قيد التوليد</span>',
                default => '<span class="badge bg-label-secondary">لم يجهز بعد</span>',
            })
            ->addColumn('actions', fn ($row) => view('tenant.auction-reports.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'content_status', 'generation_status', 'actions'];
    }

    protected function filterableColumns(): array
    {
        return ['auctions.name', 'auctions.city'];
    }

    protected function applyCustomFilters($query)
    {
        if (request()->filled('report_kind')) {
            $query->where('report_kinds.report_kind', request('report_kind'));
        }
        $status = request('content_status');
        if ($status === 'ended') {
            $query->whereHas('closing', fn ($q) => $q->whereNotNull('finalized_snapshot'));
        }
        if (in_array($status, ['complete', 'incomplete'], true)) {
            $query->whereDoesntHave('closing', fn ($q) => $q->whereNotNull('finalized_snapshot'));
            $status === 'complete' ? $query->has('marketingCampaigns') : $query->doesntHave('marketingCampaigns');
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
