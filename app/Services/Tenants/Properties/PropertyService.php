<?php

namespace App\Services\Tenants\Properties;

use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    public function getStatistics(): array
    {
        return [
            'total'               => Property::count(),
            'available'           => Property::where('status', 'available')->count(),
            'assigned_to_auction' => Property::where('status', 'assigned_to_auction')->count(),
            'completed'           => Property::where('data_status', 'completed')->count(),
        ];
    }

    public function generatePropertyNumber(?int $assignmentId = null): string
    {
        $count = Property::when($assignmentId, fn($q) => $q->where('assignment_id', $assignmentId))->count() + 1;
        return str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function createProperty(array $data, ?UploadedFile $image = null): Property
    {
        if ($image) {
            $data['main_image'] = $image->store('properties/images', 'tenant_public');
        }

        if (empty($data['property_number'])) {
            $data['property_number'] = $this->generatePropertyNumber($data['assignment_id'] ?? null);
        }

        $data['created_by'] = auth()->id();

        return Property::create($data);
    }

    public function updateProperty(Property $property, array $data, ?UploadedFile $image = null): Property
    {
        if ($image) {
            if ($property->main_image && Storage::disk('tenant_public')->exists($property->main_image)) {
                Storage::disk('tenant_public')->delete($property->main_image);
            }
            $data['main_image'] = $image->store('properties/images', 'tenant_public');
        }

        $property->update($data);

        return $property;
    }

    public function toggleStatus(Property $property): bool
    {
        $newStatus = ! $property->is_active;
        $property->update([
            'is_active' => $newStatus,
            'status'    => $newStatus ? 'available' : 'sold',
        ]);

        return $newStatus;
    }

    public function deleteProperty(Property $property): bool
    {
        return (bool) $property->delete();
    }
}
