<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use Illuminate\Support\Facades\DB;

final class AuctionReportsDataTable extends AbstractDataTable
{
    protected function buttons(): array
    {
        return [['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']]]];
    }

    protected function getTableId()
    {
        return 'auction-reports-table';
    }

    protected function resource()
    {
        $kinds = DB::query()->selectRaw("'outputs' as report_kind")->unionAll(DB::query()->selectRaw("'report' as report_kind"));

        return Auction::query()->select(['auctions.*', 'report_kinds.report_kind'])->crossJoinSub($kinds, 'report_kinds')->with(['marketingCampaigns', 'properties:id', 'closing' => fn ($q) => $q->select(['id', 'auction_id'])->selectRaw('finalized_snapshot IS NOT NULL as is_finalized'), 'publishedReportGeneration:id,status,outputs_path,report_path', 'latestReportGeneration:auction_report_generations.id,auction_report_generations.auction_id,auction_report_generations.status']);
    }

    protected function getColumns(): array
    {
        return [['data' => 'name', 'name' => 'auctions.name', 'title' => 'المزاد'], ['data' => 'kind_label', 'name' => 'report_kind', 'title' => 'التقرير'], ['data' => 'content_status', 'title' => 'حالة التقرير', 'orderable' => false, 'searchable' => false], ['data' => 'generation_status', 'title' => 'تجهيز الملف', 'orderable' => false, 'searchable' => false], ['data' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false]];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable->setRowId(fn ($row) => 'report-'.$row->id.'-'.$row->report_kind)->addColumn('kind_label', fn ($row) => $row->report_kind === 'outputs' ? 'مخرجات المزاد' : 'التقرير النهائي')->addColumn('content_status', function ($row) {
            $state = (new AuctionReportState)->forAuction($row);

            return '<span class="badge bg-label-'.$state->color().'">'.e($state->label()).'</span>';
        })->addColumn('generation_status', fn ($row) => match ($row->latestReportGeneration?->status) {
            'ready' => 'جاهز', 'failed' => 'تعذر التجهيز', 'queued', 'processing' => 'قيد التجهيز', default => 'لم يجهز بعد'
        })->addColumn('actions', fn ($row) => view('tenant.auction-reports.action', ['auction' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['content_status', 'actions'];
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
}
