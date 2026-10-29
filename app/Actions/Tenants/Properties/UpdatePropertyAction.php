<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class UpdatePropertyAction
{
    public function execute(Property $property, array $data): Property
    {
        $storedPaths = [];
        $pathsToDelete = [];

        try {
            $updated = DB::transaction(function () use ($property, $data, &$storedPaths, &$pathsToDelete): Property {
                $boundaries = $data['boundaries'] ?? null;
                $owners = $data['owners'] ?? null;
                $units = $data['units'] ?? null;

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

                // If new main image uploaded
                if ($mainImageFile instanceof UploadedFile) {
                    if ($property->main_image) {
                        $pathsToDelete[] = $property->main_image;
                    }
                    $mainImagePath = $mainImageFile->store('properties/main_images', 'tenant_public');
                    $storedPaths[] = $mainImagePath;
                    $data['main_image'] = $mainImagePath;

                    // Also create/update main image attachment
                    $property->attachments()->where('category', PropertyAttachmentCategory::MAIN_IMAGE->value)->delete();
                    $property->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $mainImagePath,
                        'original_name' => $mainImageFile->getClientOriginalName(),
                        'mime_type' => $mainImageFile->getClientMimeType(),
                        'extension' => $mainImageFile->getClientOriginalExtension(),
                        'file_size' => $mainImageFile->getSize(),
                        'category' => PropertyAttachmentCategory::MAIN_IMAGE->value,
                    ]);
                }

                // If new aerial image uploaded or modified
                if ($aerialImageInput instanceof UploadedFile) {
                    if ($property->aerial_image) {
                        $pathsToDelete[] = $property->aerial_image;
                    }
                    $aerialImagePath = $aerialImageInput->store('properties/aerial_images', 'tenant_public');
                    $storedPaths[] = $aerialImagePath;
                    $data['aerial_image'] = $aerialImagePath;

                    $property->attachments()->where('category', PropertyAttachmentCategory::AERIAL_PHOTO->value)->delete();
                    $property->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $aerialImagePath,
                        'original_name' => $aerialImageInput->getClientOriginalName(),
                        'mime_type' => $aerialImageInput->getClientMimeType(),
                        'extension' => $aerialImageInput->getClientOriginalExtension(),
                        'file_size' => $aerialImageInput->getSize(),
                        'category' => PropertyAttachmentCategory::AERIAL_PHOTO->value,
                    ]);
                } elseif (is_string($aerialImageInput) && str_starts_with($aerialImageInput, 'data:image/')) {
                    if ($property->aerial_image) {
                        $pathsToDelete[] = $property->aerial_image;
                    }
                    [$type, $imageData] = explode(';', $aerialImageInput);
                    [, $imageData] = explode(',', $imageData);
                    $decoded = base64_decode($imageData);
                    $extension = str_contains($type, 'png') ? 'png' : (str_contains($type, 'webp') ? 'webp' : 'jpg');
                    $aerialImagePath = 'properties/aerial_images/'.Str::uuid().'.'.$extension;
                    Storage::disk('tenant_public')->put($aerialImagePath, $decoded);
                    $storedPaths[] = $aerialImagePath;
                    $data['aerial_image'] = $aerialImagePath;

                    $property->attachments()->where('category', PropertyAttachmentCategory::AERIAL_PHOTO->value)->delete();
                    $property->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $aerialImagePath,
                        'original_name' => 'property_aerial_image.png',
                        'mime_type' => 'image/png',
                        'extension' => 'png',
                        'file_size' => strlen($decoded),
                        'category' => PropertyAttachmentCategory::AERIAL_PHOTO->value,
                    ]);
                } elseif (is_string($aerialImageInput) && ! empty($aerialImageInput)) {
                    $data['aerial_image'] = $aerialImageInput;
                }

                $property->update($data);

                // Update boundaries if provided
                if ($boundaries !== null) {
                    $property->boundaries()->delete();
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
                }

                // Update owners if provided
                if ($owners !== null) {
                    $property->owners()->delete();
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
                }

                // Update units if provided
                if ($units !== null) {
                    $property->units()->delete();
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
                }

                // Attach any new files
                $singleCategories = [
                    PropertyAttachmentCategory::DEED_DOCUMENT->value,
                    PropertyAttachmentCategory::CADASTRAL_PLAN->value,
                    PropertyAttachmentCategory::BUILDING_PERMIT->value,
                    PropertyAttachmentCategory::COORDINATES_STATEMENT->value,
                    PropertyAttachmentCategory::ASSIGNMENT_LETTER_IMAGE->value,
                ];

                foreach ($fileMap as $category => $uploads) {
                    if (! is_array($uploads)) {
                        $uploads = [$uploads];
                    }

                    foreach ($uploads as $upload) {
                        if (! $upload instanceof UploadedFile) {
                            continue;
                        }

                        if (in_array($category, $singleCategories, true)) {
                            $oldAttachments = $property->attachments()->where('category', $category)->get();
                            foreach ($oldAttachments as $old) {
                                if ($old->file_path) {
                                    Storage::disk($old->disk ?: 'tenant_public')->delete($old->file_path);
                                }
                                $old->delete();
                            }
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

            if (! empty($pathsToDelete)) {
                Storage::disk('tenant_public')->delete(array_values(array_unique($pathsToDelete)));
            }

            return $updated;
        } catch (Throwable $exception) {
            Storage::disk('tenant_public')->delete($storedPaths);
            throw $exception;
        }
    }

    private function parseCoordinatesFromUrl(string $url): ?array
    {
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        if (preg_match('/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        return null;
    }
}
