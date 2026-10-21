<?php

declare(strict_types=1);

namespace App\Actions\Admin\Archive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ManageCentralArchiveAction
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
