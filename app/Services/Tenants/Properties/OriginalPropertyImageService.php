<?php

declare(strict_types=1);

namespace App\Services\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Models\Tenants\Properties\Property;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class OriginalPropertyImageService
{
    /** @return list<string> Paths replaced after the surrounding transaction commits. */
    public function store(Property $property, ?string $source, array &$storedPaths): array
    {
        if (! $source || ! str_starts_with($source, 'data:image/')) {
            return [];
        }

        $encoded = explode(',', $source, 2)[1] ?? '';
        $bytes = strlen($encoded) <= 14 * 1024 * 1024 ? base64_decode($encoded, true) : false;
        $info = $bytes !== false ? @getimagesizefromstring($bytes) : false;
        $extension = match ($info['mime'] ?? '') {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => null,
        };
        if (! $extension || strlen($bytes) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['raw_aerial_image' => 'يجب أن تكون صورة العقار الأصلية صورة صالحة بحد أقصى 10MB.']);
        }

        $path = 'properties/aerial_originals/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('tenant_public')->put($path, $bytes)) {
            throw new RuntimeException('Unable to store the original property image.');
        }
        $storedPaths[] = $path;
        $originals = $property->attachments()->where('category', PropertyAttachmentCategory::AERIAL_ORIGINAL->value);
        $replaced = $originals->pluck('file_path')->all();
        $originals->delete();
        $property->attachments()->create([
            'disk' => 'tenant_public', 'file_path' => $path, 'original_name' => 'property_original.'.$extension,
            'mime_type' => $info['mime'], 'extension' => $extension, 'file_size' => strlen($bytes),
            'category' => PropertyAttachmentCategory::AERIAL_ORIGINAL->value,
        ]);

        return $replaced;
    }
}
