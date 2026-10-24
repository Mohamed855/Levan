<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Archive\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Auction;

final class AuctionArchiveDataTable extends AbstractDataTable
{
    protected function getTableId(): string { return 'auctions-archive-table'; }
    protected function resource() { return Auction::onlyTrashed()->with('assignment'); }
    protected function getColumns(): array { return [
        ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد'],
        ['data' => 'assignment', 'name' => 'assignment.name', 'title' => 'الإسناد'],
        ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
        ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
        ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
    ]; }
    protected function addCustomColumns($datatable): void { $datatable
        ->editColumn('name', fn (Auction $auction): string => '<span class="fw-semibold">'.e($auction->name).'</span>')
        ->addColumn('assignment', fn (Auction $auction): string => e($auction->assignment?->name ?? '-'))
        ->editColumn('status', fn (Auction $auction): string => '<span class="badge '.e($auction->status_badge_class).'">'.e($auction->status_label).'</span>')
        ->editColumn('deleted_at', fn (Auction $auction): string => $auction->deleted_at?->format('Y-m-d H:i') ?? '-')
        ->addColumn('actions', fn (Auction $auction): string => view('tenant.archives.auctions.action', ['id' => $auction->id])->render()); }
    protected function rawColumns(): array { return ['name', 'status', 'actions']; }
    protected function getSearchableColumns(): array { return ['name', 'status']; }
    protected function getCustomOrder(): array { return [3, 'desc']; }
    protected function buttons(): array { return [[ 'extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']] ]]; }
}
