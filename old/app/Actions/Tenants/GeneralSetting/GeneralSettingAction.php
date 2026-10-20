<?php

declare(strict_types=1);

namespace App\Actions\Tenants\GeneralSetting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class GeneralSettingAction
{
    /** @param class-string<Model> $modelClass */
    public function create(string $modelClass, array $data, int|string $userId): Model
    {
        return DB::transaction(fn (): Model => $modelClass::query()->create($data + ['user_id' => $userId]));
    }

    public function update(Model $model, array $data, int|string $userId): Model
    {
        return DB::transaction(function () use ($model, $data, $userId): Model {
            $model->update($data + ['user_id' => $userId]);

            return $model->refresh();
        });
    }

    public function toggleStatus(Model $model): string
    {
        return DB::transaction(function () use ($model): string {
            $model->update(['status' => $model->status === 'active' ? 'inactive' : 'active']);

            return (string) $model->status;
        });
    }

    public function delete(Model $model): void
    {
        DB::transaction(fn () => $model->delete());
    }
}
