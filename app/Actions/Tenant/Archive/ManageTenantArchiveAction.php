<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Archive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ManageTenantArchiveAction
{
    public function restore(Model $model): void
    {
        DB::transaction(fn () => $model->restore());
    }

    public function forceDelete(Model $model): void
    {
        DB::transaction(fn () => $model->forceDelete());
    }
}
