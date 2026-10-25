<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
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

final class CreateAuctionAction
{
    public function __construct(
        private readonly AuctionReportInputService $reportInputs,
    ) {}

    /**
     * @param array<string, mixed>|User $userOrData
     * @param array<string, mixed>|int|null $dataOrCreatorId
     * @param array<int, UploadedFile> $attachments
     * @param array<int, UploadedFile> $marketingEvidence
     * @param array<int|string, array<int, UploadedFile>> $boardImagesBefore
     * @param array<int|string, array<int, UploadedFile>> $boardImagesAfter
     */
    public function execute(
        mixed $userOrData,
        mixed $dataOrCreatorId = null,
        ?UploadedFile $coverImage = null,
        array $attachments = [],
        ?UploadedFile $reportImage = null,
        array $marketingEvidence = [],
        array $boardImagesBefore = [],
        array $boardImagesAfter = [],
    ): Auction {
        if ($userOrData instanceof User) {
            $user = $userOrData;
            $data = (array) $dataOrCreatorId;
            $creatorId = $user->id;
        } else {
            $data = (array) $userOrData;
            $creatorId = is_int($dataOrCreatorId) ? $dataOrCreatorId : null;
        }

        if (! $reportImage instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'report_image' => 'صورة التقرير مطلوبة للحفظ النهائي.',
            ]);
        }

        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $creatorId, $coverImage, $attachments, $reportImage, $marketingEvidence, $boardImagesBefore, $boardImagesAfter, &$storedPaths): Auction {
                $assignmentId = (int) ($data['assignment_id'] ?? 0);
                $assignment = Assignment::query()
                    ->whereKey($assignmentId)
                    ->lockForUpdate()
                    ->first();

                if (! $assignment) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار غير موجود.',
                    ]);
                }

                $propertyIds = $this->reportInputs->validatePropertyIds((array) ($data['property_ids'] ?? []), $assignmentId);
                $pivotData = $this->reportInputs->pivotData($propertyIds, (array) ($data['property_results'] ?? []));

                if ($coverImage instanceof UploadedFile) {
                    $coverPath = $coverImage->store('auctions/covers', 'tenant_public');
                    $storedPaths[] = $coverPath;
                    $data['cover_image'] = $coverPath;
                }

                $reportImagePath = $reportImage->store('auctions/report-images', 'tenant_public');
                $storedPaths[] = $reportImagePath;
                $data['report_image'] = $reportImagePath;
                $data['marketing_data'] = $data['marketing'] ?? null;

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

                $creator = $creatorId ?? auth('tenant')->id() ?? auth()->id();
                $data['created_by'] = $creator ? (int) $creator : null;
                if (! isset($data['status'])) {
                    $data['status'] = AuctionStatus::PENDING_APPROVAL->value;
                }
                $statusValue = is_object($data['status']) ? $data['status']->value : (string) $data['status'];
                $data['is_active'] = $statusValue === AuctionStatus::ACTIVE->value;

                $auction = Auction::query()->create($data);

                $auction->properties()->sync($pivotData);

                foreach ($attachments as $file) {
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

                return $auction->load(['properties', 'attachments', 'assignment']);
            });

            if (tenancy()->initialized && tenant('id')) {
                GenerateAuctionReportsJob::dispatchAfterCommit($auction, (string) tenant('id'));
            }

            return $auction;
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }
    }

}
