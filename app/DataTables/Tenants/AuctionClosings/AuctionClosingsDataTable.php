<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\AuctionClosings;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;

final class AuctionClosingsDataTable extends AbstractDataTable
{
    protected function buttons(): array
    {
        return [['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']]]];
    }

    protected function getTableId()
    {
        return 'auction-closings-table';
    }

    protected function resource()
    {
        return Auction::query()->with(['assignment:id,assignment_number', 'closing' => fn ($q) => $q->select(['id', 'auction_id', 'saved_at'])->selectRaw('finalized_snapshot IS NOT NULL as is_finalized')])->withCount('properties');
    }

    protected function getColumns(): array
    {
        return [['data' => 'id', 'name' => 'id', 'title' => '#'], ['data' => 'name', 'name' => 'name', 'title' => 'المزاد'], ['data' => 'assignment_number', 'title' => 'رقم الإسناد', 'orderable' => false], ['data' => 'properties_count', 'title' => 'عدد العقارات', 'searchable' => false], ['data' => 'closing_status', 'title' => 'الإقفال', 'orderable' => false, 'searchable' => false], ['data' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false]];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable->addColumn('assignment_number', fn ($row) => $row->assignment?->assignment_number ?? '-')->addColumn('closing_status', fn ($row) => $row->closing?->is_finalized ? 'منتهي' : ($row->closing ? 'محفوظ' : 'لم يبدأ'))->addColumn('actions', fn ($row) => view('tenant.auction-closings.action', ['auction' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['actions'];
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
}
