<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UpdateAuctionAction
{
    public function __construct(
        private readonly AuctionReportInputService $reportInputs,
    ) {}

    /**
     * @param array<string, mixed>|User $userOrData
     * @param array<string, mixed>|null $dataOrNull
     * @param array<int, UploadedFile> $attachments
     * @param array<int, UploadedFile> $marketingEvidence
     * @param array<int|string, array<int, UploadedFile>> $boardImagesBefore
     * @param array<int|string, array<int, UploadedFile>> $boardImagesAfter
     */
    public function execute(
        Auction $auction,
        mixed $userOrData,
        mixed $dataOrCover = null,
        mixed $coverOrAttachments = null,
        mixed $attachments = [],
        ?UploadedFile $reportImage = null,
        array $marketingEvidence = [],
        array $boardImagesBefore = [],
        array $boardImagesAfter = [],
    ): Auction {
        if ($userOrData instanceof User) {
            $data = (array) $dataOrCover;
            $cover = $coverOrAttachments instanceof UploadedFile ? $coverOrAttachments : null;
            $files = is_array($attachments) ? $attachments : [];
        } else {
            $data = (array) $userOrData;
            $cover = $dataOrCover instanceof UploadedFile ? $dataOrCover : ($coverOrAttachments instanceof UploadedFile ? $coverOrAttachments : null);
            $files = is_array($coverOrAttachments) ? $coverOrAttachments : (is_array($dataOrCover) && $dataOrCover !== $data ? $dataOrCover : (is_array($attachments) ? $attachments : []));
        }

        if (! $reportImage instanceof UploadedFile && blank($auction->report_image)) {
            throw ValidationException::withMessages([
                'report_image' => 'صورة التقرير مطلوبة للحفظ النهائي.',
            ]);
        }

        $storedPaths = [];
        $pathsToDelete = [];

        try {
            $updated = DB::transaction(function () use ($auction, $data, $cover, $files, $reportImage, $marketingEvidence, $boardImagesBefore, $boardImagesAfter, &$storedPaths, &$pathsToDelete): Auction {
                $targetAssignmentId = (int) ($data['assignment_id'] ?? $auction->assignment_id);
                $assignment = Assignment::query()
                    ->whereKey($targetAssignmentId)
                    ->lockForUpdate()
                    ->first();

                if (! $assignment) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار غير موجود.',
                    ]);
                }

                $currentProperties = $auction->properties()->get();
                $propertyIds = array_key_exists('property_ids', $data)
                    ? $this->reportInputs->validatePropertyIds((array) $data['property_ids'], $targetAssignmentId)
                    : $currentProperties->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                $syncData = $this->reportInputs->pivotData(
                    $propertyIds,
                    (array) ($data['property_results'] ?? []),
                    $currentProperties,
                );

                $removedPropertyIds = $currentProperties->pluck('id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->diff($propertyIds)
                    ->all();
                if ($removedPropertyIds !== []) {
                    $orphanedEvidence = $auction->attachments()->whereIn('property_id', $removedPropertyIds)->get();
                    $pathsToDelete = array_merge($pathsToDelete, $orphanedEvidence->pluck('file_path')->filter()->all());
                    $auction->attachments()->whereIn('property_id', $removedPropertyIds)->delete();
                }

                if ($cover instanceof UploadedFile) {
                    $coverPath = $cover->store('auctions/covers', 'tenant_public');
                    $storedPaths[] = $coverPath;
                    $data['cover_image'] = $coverPath;
                    if ($auction->cover_image) {
                        $pathsToDelete[] = $auction->cover_image;
                    }
                }

                if ($reportImage instanceof UploadedFile) {
                    $reportImagePath = $reportImage->store('auctions/report-images', 'tenant_public');
                    $storedPaths[] = $reportImagePath;
                    $data['report_image'] = $reportImagePath;
                    if ($auction->report_image) {
                        $pathsToDelete[] = $auction->report_image;
                    }
                }

                if (array_key_exists('marketing', $data)) {
                    $data['marketing_data'] = $data['marketing'];
                }

                unset(
                    $data['property_ids'],
                    $data['property_results'],
                    $data['marketing'],
                    $data['attachments'],
                    $data['marketing_evidence'],
                    $data['board_images_before'],
                    $data['board_images_after'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key'],
                    $data['_wizard_step'],
                    $data['fields'],
                    $data['target_path'],
                    $data['template_versions'],
                    $data['brochure_template_id'],
                    $data['billboard_template_id'],
                    $data['social_template_id'],
                );

                if (isset($data['status'])) {
                    $statusValue = is_object($data['status']) ? $data['status']->value : (string) $data['status'];
                    $data['is_active'] = $statusValue === AuctionStatus::ACTIVE->value;
                }

                $auction->update($data);
                $auction->properties()->sync($syncData);

                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('auctions/attachments', 'tenant_public');
                        $storedPaths[] = $path;
                        AuctionAttachment::query()->create([
                            'auction_id' => $auction->id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'category' => 'auction_doc',
                            'channels' => ['internal'],
                        ]);
                    }
                }

                $this->reportInputs->storeEvidence(
                    $auction,
                    $marketingEvidence,
                    $boardImagesBefore,
                    $boardImagesAfter,
                    $propertyIds,
                    $storedPaths,
                );

                return $auction->fresh(['properties', 'attachments', 'assignment']);
            });
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }

        Storage::disk('tenant_public')->delete(array_values(array_unique($pathsToDelete)));

        return $updated;
    }
}
