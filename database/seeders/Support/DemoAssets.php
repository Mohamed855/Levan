<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DemoAssets
{
    public static function image(string $path, string $disk = 'tenant_public'): string
    {
        return self::copy('images.jpg', $path, $disk);
    }

    public static function document(string $path, string $disk = 'tenant_public'): string
    {
        return self::copy('sample.pdf', $path, $disk);
    }

    public static function uploadImage(): UploadedFile
    {
        return new UploadedFile(database_path('seeders/fixtures/images.jpg'), 'images.jpg', 'image/jpeg', test: true);
    }

    public static function uploadDocument(): UploadedFile
    {
        return new UploadedFile(database_path('seeders/fixtures/sample.pdf'), 'sample.pdf', 'application/pdf', test: true);
    }

    public static function assignment(Assignment $assignment): void
    {
        foreach (AssignmentAttachmentCategory::cases() as $category) {
            self::attachment($assignment, $category->value, $category === AssignmentAttachmentCategory::IMAGE);
        }
    }

    public static function property(Property $property): void
    {
        $paths = [];
        foreach (PropertyAttachmentCategory::cases() as $category) {
            $isImage = in_array($category, [PropertyAttachmentCategory::MAIN_IMAGE, PropertyAttachmentCategory::GALLERY_IMAGE,
                PropertyAttachmentCategory::AERIAL_PHOTO, PropertyAttachmentCategory::ASSIGNMENT_LETTER_IMAGE], true);
            $paths[$category->value] = self::attachment($property, $category->value, $isImage);
        }
        $property->update([
            'main_image' => $paths[PropertyAttachmentCategory::MAIN_IMAGE->value],
            'aerial_image' => $paths[PropertyAttachmentCategory::AERIAL_PHOTO->value],
        ]);
    }

    private static function attachment(Assignment|Property $owner, string $category, bool $isImage): string
    {
        $filename = $isImage ? 'images.jpg' : 'sample.pdf';
        $extension = $isImage ? 'jpg' : 'pdf';
        $path = self::copy($filename, 'demo/'.$owner->getTable().'/'.$owner->getKey().'/'.$category.'.'.$extension, 'tenant_public');
        $owner->attachments()->updateOrCreate(['file_path' => $path], [
            'disk' => 'tenant_public', 'original_name' => $filename,
            'mime_type' => $isImage ? 'image/jpeg' : 'application/pdf',
            'extension' => $extension, 'file_size' => Storage::disk('tenant_public')->size($path), 'category' => $category,
        ]);

        return $path;
    }

    private static function copy(string $filename, string $path, string $disk): string
    {
        if (! Storage::disk($disk)->put($path, file_get_contents(database_path('seeders/fixtures/'.$filename)))) {
            throw new RuntimeException('Could not store demo fixture on disk ['.$disk.']: '.$path);
        }

        return $path;
    }
}
