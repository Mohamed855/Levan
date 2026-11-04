<?php

declare(strict_types=1);

namespace App\Services\Tenants\Assignments;

use App\Models\Tenants\Assignments\IssuingEntity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            $imagePath = null;
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $imagePath = $data['image']->store('issuing-entities', 'tenant_public');
            } elseif (isset($data['image']) && is_string($data['image'])) {
                $imagePath = $data['image'];
            }

            return IssuingEntity::create([
                'name' => trim((string) $data['name']),
                'image' => $imagePath,
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

            $updateData = ['name' => $newName];
            if (array_key_exists('is_active', $data)) {
                $requestedActive = (bool) $data['is_active'];
                if (! $requestedActive && $entity->is_active && $entity->assignments()->exists()) {
                    throw new LogicException('لا يمكن تعطيل جهة مسندة مرتبطة بإسنادات في النظام.');
                }
                $updateData['is_active'] = $requestedActive;
            }

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($entity->image && Storage::disk('tenant_public')->exists($entity->image)) {
                    Storage::disk('tenant_public')->delete($entity->image);
                }
                $updateData['image'] = $data['image']->store('issuing-entities', 'tenant_public');
            } elseif (! empty($data['remove_image'])) {
                if ($entity->image && Storage::disk('tenant_public')->exists($entity->image)) {
                    Storage::disk('tenant_public')->delete($entity->image);
                }
                $updateData['image'] = null;
            }

            $entity->update($updateData);

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
        if ($entity->assignments()->exists()) {
            throw new LogicException('لا يمكن تعطيل جهة مسندة مرتبطة بإسنادات في النظام.');
        }

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
