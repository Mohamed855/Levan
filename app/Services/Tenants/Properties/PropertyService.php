<?php

declare(strict_types=1);

namespace App\Services\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Models\Tenants\Properties\Property;
use Illuminate\Support\Facades\Storage;

class PropertyService
{
    public function getStatistics(): array
    {
        return [
            'total'                => Property::count(),
            'active'               => Property::where('is_active', true)->count(),
            'ready_for_assignment' => Property::where('data_status', PropertyDataStatus::COMPLETED->value)->whereNull('assignment_id')->count(),
            'completed'            => Property::where('data_status', PropertyDataStatus::COMPLETED->value)->count(),
            'unlinked'             => Property::whereNull('assignment_id')->count(),
            'needs_review'         => Property::where('data_status', '!=', PropertyDataStatus::COMPLETED->value)->count(),
        ];
    }

    public function generatePropertyNumber(?int $assignmentId = null): string
    {
        $count = Property::when($assignmentId, fn ($q) => $q->where('assignment_id', $assignmentId))->count() + 1;

        return str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function toggleStatus(Property $property, ?bool $isActive = null): bool
    {
        $newStatus = $isActive ?? ! $property->is_active;
        $property->update([
            'is_active' => $newStatus,
        ]);

        return $newStatus;
    }

    public function deleteProperty(Property $property): bool
    {
        return (bool) $property->delete();
    }

    public function forceDeleteProperty(Property $property): bool
    {
        $mainImage = $property->main_image;
        $deleted = (bool) $property->forceDelete();

        if ($deleted && $mainImage) {
            Storage::disk('tenant_public')->delete($mainImage);
        }

        return $deleted;
    }
}
