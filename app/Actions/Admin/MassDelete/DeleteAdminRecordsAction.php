<?php

declare(strict_types=1);

namespace App\Actions\Admin\MassDelete;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DeleteAdminRecordsAction
{
    public function execute(Collection $records): int
    {
        return DB::connection(config('tenancy.database.central_connection', config('database.default')))->transaction(function () use ($records): int {
            return $records->sum(fn ($record): int => (int) $record->delete());
        });
    }
}
