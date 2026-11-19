<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Auctions;

use Illuminate\Database\Eloquent\Builder;

final class PendingAuctionsDataTable extends AuctionsDataTable
{
    protected string $actionView = 'tenant.pending-auctions.action';

    protected function getTableId(): string
    {
        return 'pending-auctions-table';
    }

    protected function resource(): Builder
    {
        return parent::resource()->pending();
    }

    protected function getColumns(): array
    {
        return array_values(array_filter(parent::getColumns(), fn (array $column): bool => ! in_array($column['data'], ['checkbox', 'outputs'], true)));
    }

    protected function buttons(): array
    {
        return [];
    }

    protected function applyCustomFilters($query): Builder
    {
        return parent::applyCustomFilters($query)
            ->when(request()->filled('name'), fn (Builder $query): Builder => $query->where('name', 'like', '%'.request()->string('name')->toString().'%'));
    }
}
