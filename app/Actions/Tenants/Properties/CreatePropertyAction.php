<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Properties\PropertyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class CreatePropertyAction
{
    public function __construct(private readonly PropertyService $propertyService) {}

    public function execute(array $data, int $creatorId): Property
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $creatorId, &$storedPaths): Property {
                $boundaries = $data['boundaries'] ?? [];
                $owners = $data['owners'] ?? [];
                $units = $data['units'] ?? [];

                // Files categorization
                $fileMap = [
                    PropertyAttachmentCategory::DEED_DOCUMENT->value => isset($data['deed_document']) ? [$data['deed_document']] : [],
                    PropertyAttachmentCategory::CADASTRAL_PLAN->value => isset($data['cadastral_plan']) ? [$data['cadastral_plan']] : [],
                    PropertyAttachmentCategory::BUILDING_PERMIT->value => isset($data['building_permit']) ? [$data['building_permit']] : [],
                    PropertyAttachmentCategory::COORDINATES_STATEMENT->value => isset($data['coordinates_statement']) ? [$data['coordinates_statement']] : [],
                    PropertyAttachmentCategory::ASSIGNMENT_LETTER_IMAGE->value => isset($data['assignment_letter_image']) ? [$data['assignment_letter_image']] : [],
                    PropertyAttachmentCategory::ADDITIONAL_DOCUMENT->value => $data['additional_documents'] ?? [],
                    PropertyAttachmentCategory::GALLERY_IMAGE->value => $data['property_images'] ?? [],
                ];

                $mainImageFile = $data['main_image'] ?? null;
                $aerialImageInput = $data['aerial_image'] ?? null;

                // Remove non-column data from array
                unset(
                    $data['boundaries'],
                    $data['owners'],
                    $data['units'],
                    $data['deed_document'],
                    $data['cadastral_plan'],
                    $data['building_permit'],
                    $data['coordinates_statement'],
                    $data['assignment_letter_image'],
                    $data['additional_documents'],
                    $data['property_images'],
                    $data['main_image'],
                    $data['aerial_image'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key']
                );

                if (empty($data['property_number'])) {
                    $data['property_number'] = $this->propertyService->generatePropertyNumber();
                }

                // Store main image
                if ($mainImageFile instanceof UploadedFile) {
                    $mainImagePath = $mainImageFile->store('properties/main_images', 'tenant_public');
                    $storedPaths[] = $mainImagePath;
                    $data['main_image'] = $mainImagePath;
                }

                // Store aerial image (uploaded file or base64 rendered data)
                if ($aerialImageInput instanceof UploadedFile) {
                    $aerialImagePath = $aerialImageInput->store('properties/aerial_images', 'tenant_public');
                    $storedPaths[] = $aerialImagePath;
                    $data['aerial_image'] = $aerialImagePath;
                } elseif (is_string($aerialImageInput) && str_starts_with($aerialImageInput, 'data:image/')) {
                    [$type, $imageData] = explode(';', $aerialImageInput);
                    [, $imageData] = explode(',', $imageData);
                    $decoded = base64_decode($imageData);
                    $extension = str_contains($type, 'png') ? 'png' : (str_contains($type, 'webp') ? 'webp' : 'jpg');
                    $aerialImagePath = 'properties/aerial_images/'.Str::uuid().'.'.$extension;
                    Storage::disk('tenant_public')->put($aerialImagePath, $decoded);
                    $storedPaths[] = $aerialImagePath;
                    $data['aerial_image'] = $aerialImagePath;
                } elseif (is_string($aerialImageInput) && ! empty($aerialImageInput)) {
                    $data['aerial_image'] = $aerialImageInput;
                }

                $data['created_by'] = $creatorId;
                $data['data_status'] = PropertyDataStatus::COMPLETED->value;
                $data['is_active'] = true;

                $property = Property::query()->create($data);

                // Create main image attachment record
                if ($mainImageFile instanceof UploadedFile) {
                    $property->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $data['main_image'],
                        'original_name' => $mainImageFile->getClientOriginalName(),
                        'mime_type' => $mainImageFile->getClientMimeType(),
                        'extension' => $mainImageFile->getClientOriginalExtension(),
                        'file_size' => $mainImageFile->getSize(),
                        'category' => PropertyAttachmentCategory::MAIN_IMAGE->value,
                    ]);
                }

                // Create aerial image attachment record
                if (! empty($data['aerial_image'])) {
                    $fileSize = Storage::disk('tenant_public')->exists($data['aerial_image'])
                        ? Storage::disk('tenant_public')->size($data['aerial_image'])
                        : 0;

                    $property->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $data['aerial_image'],
                        'original_name' => 'property_aerial_image.png',
                        'mime_type' => 'image/png',
                        'extension' => 'png',
                        'file_size' => $fileSize,
                        'category' => PropertyAttachmentCategory::AERIAL_PHOTO->value,
                    ]);
                }

                // Insert boundaries
                foreach ($boundaries as $index => $boundary) {
                    if (empty($boundary['direction'])) {
                        continue;
                    }
                    $property->boundaries()->create([
                        'direction' => $boundary['direction'],
                        'border_type' => $boundary['border_type'] ?? null,
                        'border_description' => $boundary['border_description'] ?? null,
                        'length' => (float) ($boundary['length'] ?? 0),
                        'sort_order' => $index,
                    ]);
                }

                // Insert owners
                foreach ($owners as $index => $owner) {
                    if (empty($owner['owner_name'])) {
                        continue;
                    }
                    $property->owners()->create([
                        'owner_name' => $owner['owner_name'],
                        'national_id_or_cr' => $owner['national_id_or_cr'] ?? '',
                        'sort_order' => $index,
                    ]);
                }

                // Insert units
                foreach ($units as $index => $unit) {
                    if (empty($unit['unit_number'])) {
                        continue;
                    }
                    $property->units()->create([
                        'unit_number' => $unit['unit_number'],
                        'unit_type' => $unit['unit_type'] ?? null,
                        'area' => (float) ($unit['area'] ?? 0),
                        'current_use' => $unit['current_use'] ?? null,
                        'status' => $unit['status'] ?? null,
                        'sort_order' => $index,
                    ]);
                }

                // Store files and create attachments
                foreach ($fileMap as $category => $uploads) {
                    if (! is_array($uploads)) {
                        $uploads = [$uploads];
                    }

                    foreach ($uploads as $upload) {
                        if (! $upload instanceof UploadedFile) {
                            continue;
                        }

                        $folder = match ($category) {
                            PropertyAttachmentCategory::DEED_DOCUMENT->value => 'properties/deeds',
                            PropertyAttachmentCategory::CADASTRAL_PLAN->value => 'properties/plans',
                            PropertyAttachmentCategory::COORDINATES_STATEMENT->value => 'properties/coordinates',
                            PropertyAttachmentCategory::GALLERY_IMAGE->value => 'properties/gallery',
                            default => 'properties/documents',
                        };

                        $path = $upload->store($folder, 'tenant_public');
                        $storedPaths[] = $path;

                        $property->attachments()->create([
                            'disk' => 'tenant_public',
                            'file_path' => $path,
                            'original_name' => $upload->getClientOriginalName(),
                            'mime_type' => $upload->getClientMimeType(),
                            'extension' => $upload->getClientOriginalExtension(),
                            'file_size' => $upload->getSize(),
                            'category' => $category,
                        ]);
                    }
                }

                return $property->load(['boundaries', 'owners', 'units', 'attachments']);
            });
        } catch (Throwable $exception) {
            Storage::disk('tenant_public')->delete($storedPaths);
            throw $exception;
        }
    }

    private function parseCoordinatesFromUrl(string $url): ?array
    {
        // Match @lat,lng e.g. @24.7136,46.6753
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        // Match q=lat,lng e.g. q=24.7136,46.6753
        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        // Match ll=lat,lng e.g. ll=24.7136,46.6753
        if (preg_match('/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        return null;
    }
}
