<?php

declare(strict_types=1);

namespace App\Actions\Tenants\MassDelete;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DeleteTenantRecordsAction
{
    public function execute(Collection $records): int
    {
        return DB::transaction(function () use ($records): int {
            return $records->sum(fn ($record): int => (int) $record->delete());
        });
    }
}
