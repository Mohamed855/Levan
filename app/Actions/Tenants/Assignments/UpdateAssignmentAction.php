<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Assignments\AssignmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UpdateAssignmentAction
{
    public function __construct(private readonly AssignmentService $assignmentService) {}

    public function execute(
        Assignment $assignment,
        array $data,
        ?UploadedFile $image = null,
        ?UploadedFile $letter = null,
        array $generalDocuments = [],
        array $additionalAttachments = [],
    ): Assignment {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($assignment, $data, $image, $letter, $generalDocuments, $additionalAttachments, &$storedPaths): Assignment {
                $assignment = Assignment::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();

                if (array_key_exists('property_ids', $data)) {
                    $this->syncProperties($assignment, (array) $data['property_ids']);
                }

                if ($letter instanceof UploadedFile) {
                    $path = $letter->store('assignments/attachments', 'tenant_public');
                    $storedPaths[] = $path;
                    $assignment->attachments()->create([
                        'disk' => 'tenant_public',
                        'file_path' => $path,
                        'original_name' => $letter->getClientOriginalName(),
                        'mime_type' => $letter->getClientMimeType(),
                        'extension' => $letter->getClientOriginalExtension(),
                        'file_size' => $letter->getSize(),
                        'category' => AssignmentAttachmentCategory::LETTER->value,
                    ]);
                }

                foreach ($generalDocuments as $doc) {
                    if ($doc instanceof UploadedFile) {
                        $path = $doc->store('assignments/attachments', 'tenant_public');
                        $storedPaths[] = $path;
                        $assignment->attachments()->create([
                            'disk' => 'tenant_public',
                            'file_path' => $path,
                            'original_name' => $doc->getClientOriginalName(),
                            'mime_type' => $doc->getClientMimeType(),
                            'extension' => $doc->getClientOriginalExtension(),
                            'file_size' => $doc->getSize(),
                            'category' => AssignmentAttachmentCategory::GENERAL_DOCUMENT->value,
                        ]);
                    }
                }

                foreach ($additionalAttachments as $doc) {
                    if ($doc instanceof UploadedFile) {
                        $path = $doc->store('assignments/attachments', 'tenant_public');
                        $storedPaths[] = $path;
                        $assignment->attachments()->create([
                            'disk' => 'tenant_public',
                            'file_path' => $path,
                            'original_name' => $doc->getClientOriginalName(),
                            'mime_type' => $doc->getClientMimeType(),
                            'extension' => $doc->getClientOriginalExtension(),
                            'file_size' => $doc->getSize(),
                            'category' => AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value,
                        ]);
                    }
                }

                unset(
                    $data['property_ids'],
                    $data['assignment_image'],
                    $data['assignment_letter'],
                    $data['general_documents'],
                    $data['additional_attachments'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key'],
                );

                return $this->assignmentService->updateAssignment($assignment, $data, $image);
            });
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }
    }

    /** @param array<int, int|string> $propertyIds */
    private function syncProperties(Assignment $assignment, array $propertyIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $propertyIds)));
        $properties = Property::withTrashed()
            ->where(fn ($query) => $query->where('assignment_id', $assignment->id)->orWhereIn('id', $ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $selected = $properties->whereIn('id', $ids);

        if ($selected->count() !== count($ids) || $selected->contains(
            fn (Property $property): bool => $property->trashed()
                || ($property->assignment_id !== null && (int) $property->assignment_id !== (int) $assignment->id),
        )) {
            throw ValidationException::withMessages(['property_ids' => 'أحد العقارات المختارة غير متاح للربط بهذا الإسناد.']);
        }

        $removedIds = $properties->filter(
            fn (Property $property): bool => ! $property->trashed()
                && (int) $property->assignment_id === (int) $assignment->id
                && ! in_array((int) $property->id, $ids, true),
        )->modelKeys();
        $newIds = $selected->whereNull('assignment_id')->modelKeys();

        // Preserve historical auctions too; soft deletion does not dissolve their property links.
        if (DB::table('auction_properties')->whereIn('property_id', [...$removedIds, ...$newIds])->exists()) {
            throw ValidationException::withMessages(['property_ids' => 'لا يمكن تغيير إسناد عقار مرتبط بمزاد قبل معالجة ارتباطه بالمزاد.']);
        }

        Property::query()->whereKey($removedIds)->where('assignment_id', $assignment->id)->update(['assignment_id' => null]);
        Property::query()->whereKey($newIds)->whereNull('assignment_id')->update(['assignment_id' => $assignment->id]);
    }
}
