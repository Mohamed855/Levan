<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateAuctionAction
{
    public function __construct(
        private readonly AuctionReportInputService $reportInputs,
        private readonly AuctionReportService $reportService,
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

        $storedPaths = [];

        try {
            $auction = DB::transaction(function () use ($data, $creatorId, $coverImage, $attachments, $reportImage, $marketingEvidence, $boardImagesBefore, $boardImagesAfter, &$storedPaths): Auction {
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

                if (! $assignment->is_active) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار غير نشط ولا يمكن ربطه بالمزاد.',
                    ]);
                }

                $today = now()->startOfDay();
                if ($assignment->end_date && $assignment->end_date->startOfDay()->lt($today)) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار منتهي الصلاحية بتاريخ '.$assignment->end_date->format('Y-m-d').'.',
                    ]);
                }

                $startDate = $data['start_date'] ?? null;
                $endDate = $data['end_date'] ?? null;
                if ($startDate && $assignment->start_date && Carbon::parse((string) $startDate)->startOfDay()->lt($assignment->start_date->startOfDay())) {
                    throw ValidationException::withMessages([
                        'start_date' => 'تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.$assignment->start_date->format('Y-m-d').').',
                    ]);
                }
                if ($endDate && $assignment->end_date && Carbon::parse((string) $endDate)->startOfDay()->gt($assignment->end_date->startOfDay())) {
                    throw ValidationException::withMessages([
                        'end_date' => 'تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.$assignment->end_date->format('Y-m-d').').',
                    ]);
                }

                $startTime = $data['start_time'] ?? null;
                $endTime = $data['end_time'] ?? null;
                if ($startDate && $endDate && $startDate === $endDate && $startTime && $endTime) {
                    try {
                        $start = Carbon::createFromFormat('H:i', (string) $startTime);
                        $end = Carbon::createFromFormat('H:i', (string) $endTime);
                        if ($end->lessThanOrEqualTo($start)) {
                            throw ValidationException::withMessages([
                                'end_time' => 'وقت نهاية المزاد يجب أن يكون بعد وقت البداية عندما يكون المزاد في نفس اليوم.',
                            ]);
                        }
                    } catch (\Throwable $e) {
                        if ($e instanceof ValidationException) {
                            throw $e;
                        }
                    }
                }

                $propertyIds = $this->reportInputs->validatePropertyIds((array) ($data['property_ids'] ?? []), $assignmentId);
                $pivotData = $this->reportInputs->pivotData($propertyIds, (array) ($data['property_results'] ?? []));

                if ($coverImage instanceof UploadedFile) {
                    $coverPath = $coverImage->store('auctions/covers', 'tenant_public');
                    $storedPaths[] = $coverPath;
                    $data['cover_image'] = $coverPath;
                }

                if ($reportImage instanceof UploadedFile) {
                    $reportImagePath = $reportImage->store('auctions/report-images', 'tenant_public');
                    $storedPaths[] = $reportImagePath;
                    $data['report_image'] = $reportImagePath;
                }
                $data['marketing_data'] = $data['marketing'] ?? null;
                $evidenceMetadata = (array) ($data['marketing_evidence_meta'] ?? []);

                unset(
                    $data['property_ids'],
                    $data['property_results'],
                    $data['marketing'],
                    $data['attachments'],
                    $data['marketing_evidence'],
                    $data['marketing_evidence_meta'],
                    $data['report_attachments'],
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
                $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;
                unset($data['status']);

                $auction = Auction::query()->create($data);

                $auction->properties()->sync($pivotData);

                foreach ($pivotData as $pId => $pData) {
                    $resultStatus = $pData['result_status'] ?? 'pending';
                    if ($resultStatus === 'sold') {
                        \App\Models\Tenants\Properties\Property::query()->where('id', $pId)->update(['status' => 'sold']);
                    } elseif ($resultStatus === 'unsold') {
                        \App\Models\Tenants\Properties\Property::query()->where('id', $pId)->update(['status' => 'available']);
                    } else {
                        \App\Models\Tenants\Properties\Property::query()->where('id', $pId)->where('status', '!=', 'sold')->update(['status' => 'assigned_to_auction']);
                    }
                }

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
                    $evidenceMetadata,
                );

                return $auction->load(['properties', 'attachments', 'assignment']);
            });
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }

        if (tenancy()->initialized && tenant('id')) {
            $this->reportService->requestGeneration($auction, (string) tenant('id'));
        }

        return $auction;
    }

}
