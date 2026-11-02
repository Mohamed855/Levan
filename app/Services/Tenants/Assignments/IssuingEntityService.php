<?php

declare(strict_types=1);

namespace App\Services\Tenants\Assignments;

use App\Models\Tenants\Assignments\IssuingEntity;
use Illuminate\Support\Facades\DB;
use LogicException;

class IssuingEntityService
{
    public function getStatistics(): array
    {
        return [
            'total' => IssuingEntity::count(),
            'active' => IssuingEntity::where('is_active', true)->count(),
            'inactive' => IssuingEntity::where('is_active', false)->count(),
        ];
    }

    public function create(array $data, int $userId): IssuingEntity
    {
        return DB::transaction(function () use ($data, $userId): IssuingEntity {
            return IssuingEntity::create([
                'name' => trim((string) $data['name']),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $userId,
            ]);
        });
    }

    public function update(IssuingEntity $entity, array $data): IssuingEntity
    {
        return DB::transaction(function () use ($entity, $data): IssuingEntity {
            $newName = trim((string) $data['name']);
            $oldName = $entity->name;

            $entity->update([
                'name' => $newName,
                'is_active' => (bool) ($data['is_active'] ?? $entity->is_active),
            ]);

            // Keep assigned assignments text field in sync if name changed
            if ($oldName !== $newName) {
                $entity->assignments()->update(['issuing_entity' => $newName]);
            }

            return $entity->fresh();
        });
    }

    public function delete(IssuingEntity $entity): bool
    {
        if ($entity->assignments()->exists()) {
            throw new LogicException('لا يمكن حذف الجهة المسندة لأنها مرتبطة بإسنادات حالية في النظام.');
        }

        return DB::transaction(fn (): bool => (bool) $entity->delete());
    }

    public function toggleStatus(IssuingEntity $entity, ?bool $status = null): bool
    {
        $newStatus = $status ?? ! $entity->is_active;

        $entity->update(['is_active' => $newStatus]);

        return (bool) $entity->fresh()->is_active;
    }

    /**
     * @return array<int|string, string>
     */
    public function getActiveOptions(): array
    {
        return IssuingEntity::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
