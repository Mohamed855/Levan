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
                if (isset($data['property_ids'])) {
                    $newPropertyIds = array_values(array_unique(array_map('intval', (array) $data['property_ids'])));

                    // Detach properties no longer selected
                    Property::query()
                        ->where('assignment_id', $assignment->id)
                        ->whereNotIn('id', $newPropertyIds)
                        ->update(['assignment_id' => null]);

                    // Attach newly selected properties
                    if (! empty($newPropertyIds)) {
                        Property::query()
                            ->whereIn('id', $newPropertyIds)
                            ->update(['assignment_id' => $assignment->id]);
                    }
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
}
