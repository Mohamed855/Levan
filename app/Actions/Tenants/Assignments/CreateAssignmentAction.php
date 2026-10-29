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

final class CreateAssignmentAction
{
    public function __construct(private readonly AssignmentService $assignmentService) {}

    public function execute(
        array $data,
        int $creatorId,
        ?UploadedFile $image = null,
        ?UploadedFile $letter = null,
        array $generalDocuments = [],
        array $additionalAttachments = [],
    ): Assignment {
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $data,
                $creatorId,
                $image,
                $letter,
                $generalDocuments,
                $additionalAttachments,
                &$storedPaths
            ): Assignment {
                $propertyIds = array_values(array_unique(array_map('intval', (array) ($data['property_ids'] ?? []))));

                $image = $image ?? ($data['assignment_image'] ?? null);
                $letter = $letter ?? ($data['assignment_letter'] ?? null);

                $rawGeneral = ! empty($generalDocuments) ? $generalDocuments : ($data['general_documents'] ?? []);
                $generalDocs = is_array($rawGeneral) ? $rawGeneral : array_filter([$rawGeneral]);

                $rawAdditional = ! empty($additionalAttachments) ? $additionalAttachments : ($data['additional_attachments'] ?? []);
                $additionalDocs = is_array($rawAdditional) ? $rawAdditional : array_filter([$rawAdditional]);

                $files = [
                    AssignmentAttachmentCategory::IMAGE->value => array_filter([$image]),
                    AssignmentAttachmentCategory::LETTER->value => array_filter([$letter]),
                    AssignmentAttachmentCategory::GENERAL_DOCUMENT->value => array_filter($generalDocs),
                    AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value => array_filter($additionalDocs),
                ];

                unset(
                    $data['property_ids'],
                    $data['assignment_image'],
                    $data['assignment_letter'],
                    $data['general_documents'],
                    $data['additional_attachments'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key'],
                    $data['status'],
                );

                if (empty($data['assignment_number'])) {
                    $data['assignment_number'] = $this->assignmentService->generateAssignmentNumber();
                }

                $data['created_by'] = $creatorId;
                $data['is_active'] = (bool) ($data['is_active'] ?? true);
                if (isset($data['category']) && $data['category'] instanceof \BackedEnum) {
                    $data['category'] = $data['category']->value;
                }

                $assignment = Assignment::query()->create($data);

                $properties = Property::query()
                    ->whereKey($propertyIds)
                    ->whereNull('assignment_id')
                    ->lockForUpdate()
                    ->get(['id']);
                if ($properties->count() !== count($propertyIds)) {
                    throw ValidationException::withMessages([
                        'property_ids' => 'أحد العقارات المختارة غير موجود أو مرتبط بإسناد آخر.',
                    ]);
                }

                Property::query()->whereKey($propertyIds)->update(['assignment_id' => $assignment->getKey()]);

                foreach ($files as $category => $uploads) {
                    foreach ($uploads as $upload) {
                        if (! $upload instanceof UploadedFile) {
                            continue;
                        }

                        $path = $upload->store('assignments/attachments', 'tenant_public');
                        $storedPaths[] = $path;
                        $assignment->attachments()->create([
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

                return $assignment->load(['properties', 'attachments']);
            });
        } catch (Throwable $exception) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $exception;
        }
    }
}
