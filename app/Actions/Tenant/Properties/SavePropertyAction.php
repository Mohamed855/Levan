<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Properties;

use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SavePropertyAction
{
    /** @param array<string,mixed> $data */
    public function create(User $user, array $data): Property
    {
        return $this->save(new Property(), $user, $data);
    }

    /** @param array<string,mixed> $data */
    public function update(Property $property, User $user, array $data): Property
    {
        return $this->save($property, $user, $data);
    }

    /** @param array<string,mixed> $data */
    private function save(Property $property, User $user, array $data): Property
    {
        return DB::transaction(function () use ($property, $user, $data): Property {
            $boundaries = $data['boundaries'] ?? [];
            $owners = $data['owners'] ?? [];
            $units = $data['units'] ?? [];

            // Extract file uploads
            $mainImageFile = $data['main_image'] ?? null;
            $aerialImageInput = $data['aerial_image'] ?? null;
            $deedDoc = $data['deed_document'] ?? null;
            $cadastralPlan = $data['cadastral_plan'] ?? null;
            $buildingPermit = $data['building_permit'] ?? null;
            $coordinatesStmt = $data['coordinates_statement'] ?? null;
            $assignmentLetter = $data['assignment_letter_image'] ?? null;
            $additionalDocs = $data['additional_documents'] ?? [];
            $propertyImages = $data['property_images'] ?? [];

            unset(
                $data['boundaries'],
                $data['owners'],
                $data['units'],
                $data['main_image'],
                $data['aerial_image'],
                $data['deed_document'],
                $data['cadastral_plan'],
                $data['building_permit'],
                $data['coordinates_statement'],
                $data['assignment_letter_image'],
                $data['additional_documents'],
                $data['property_images'],
                $data['_wizard_draft_id'],
                $data['_wizard_resource_key']
            );

            // Handle main image
            if ($mainImageFile instanceof UploadedFile) {
                $path = $mainImageFile->store('properties/main_images', 'public');
                $data['main_image'] = $path;
            } elseif (is_string($mainImageFile) && ! empty($mainImageFile)) {
                $data['main_image'] = $mainImageFile;
            }

            // Handle aerial image
            if ($aerialImageInput instanceof UploadedFile) {
                $path = $aerialImageInput->store('properties/aerial_images', 'public');
                $data['aerial_image'] = $path;
            } elseif (is_string($aerialImageInput) && str_starts_with($aerialImageInput, 'data:image/')) {
                [$type, $imageData] = explode(';', $aerialImageInput);
                [, $imageData] = explode(',', $imageData);
                $decoded = base64_decode($imageData);
                $extension = str_contains($type, 'png') ? 'png' : 'jpg';
                $path = 'properties/aerial_images/' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->put($path, $decoded);
                $data['aerial_image'] = $path;
            } elseif (is_string($aerialImageInput) && ! empty($aerialImageInput)) {
                $data['aerial_image'] = $aerialImageInput;
            }

            if (empty($data['created_by'])) {
                $data['created_by'] = $property->created_by ?: $user->id;
            }

            if (! isset($data['data_status'])) {
                $data['data_status'] = PropertyDataStatus::COMPLETED->value;
            }

            $property->fill($data);
            $property->save();

            // Re-sync boundaries, owners, units
            if (! empty($boundaries)) {
                $property->boundaries()->delete();
                foreach ($boundaries as $i => $row) {
                    $property->boundaries()->create($row + ['sort_order' => $i]);
                }
            }

            if (! empty($owners)) {
                $property->owners()->delete();
                foreach ($owners as $i => $row) {
                    $property->owners()->create($row + ['sort_order' => $i]);
                }
            }

            if (! empty($units)) {
                $property->units()->delete();
                foreach ($units as $i => $row) {
                    $property->units()->create($row + ['sort_order' => $i]);
                }
            }

            // Sync attachments
            $this->saveAttachment($property, $deedDoc, PropertyAttachmentCategory::DEED_DOCUMENT->value);
            $this->saveAttachment($property, $cadastralPlan, PropertyAttachmentCategory::CADASTRAL_PLAN->value);
            $this->saveAttachment($property, $buildingPermit, PropertyAttachmentCategory::BUILDING_PERMIT->value);
            $this->saveAttachment($property, $coordinatesStmt, PropertyAttachmentCategory::COORDINATES_STATEMENT->value);
            $this->saveAttachment($property, $assignmentLetter, PropertyAttachmentCategory::ASSIGNMENT_LETTER_IMAGE->value);

            if (is_array($additionalDocs)) {
                foreach ($additionalDocs as $doc) {
                    $this->saveAttachment($property, $doc, PropertyAttachmentCategory::ADDITIONAL_DOCUMENT->value);
                }
            }

            if (is_array($propertyImages)) {
                foreach ($propertyImages as $img) {
                    $this->saveAttachment($property, $img, PropertyAttachmentCategory::GALLERY_IMAGE->value);
                }
            }

            return $property;
        });
    }

    private function saveAttachment(Property $property, mixed $file, string $category): void
    {
        if ($file instanceof UploadedFile) {
            $path = $file->store('properties/attachments', 'public');
            $property->attachments()->create([
                'disk' => 'public',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'category' => $category,
            ]);
        }
    }
}