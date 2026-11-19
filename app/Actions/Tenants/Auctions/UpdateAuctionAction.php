<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\Auctions\AuctionBrokersManager;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class UpdateAuctionAction
{
    public function __construct(
        private readonly AuctionReportInputService $reportInputs,
        private readonly AuctionReportService $reportService,
    ) {}

    /**
     * @param  array<string, mixed>|User  $userOrData
     * @param  array<string, mixed>|null  $dataOrNull
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int, UploadedFile>  $marketingEvidence
     * @param  array<int|string, array<int, UploadedFile>>  $boardImagesBefore
     * @param  array<int|string, array<int, UploadedFile>>  $boardImagesAfter
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
        array $brochureEvidence = [],
        array $platformEvidence = [],
        array $brokerEvidence = [],
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

        // Only an actual replacement upload changes an existing image path.
        if (! empty($data['property_results'])) {
            throw ValidationException::withMessages(['auction' => 'تُحدّث نتائج الإقفال من صفحة تفاصيل المزاد عند انتهاء المزاد.']);
        }
        unset($data['report_image'], $data['cover_image']);
        $storedPaths = [];
        $pathsToDelete = [];

        try {
            $updated = DB::transaction(function () use ($auction, $data, $cover, $files, $reportImage, $marketingEvidence, $boardImagesBefore, $boardImagesAfter, $brochureEvidence, $platformEvidence, $brokerEvidence, &$storedPaths, &$pathsToDelete): Auction {
                $auction = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
                app(AuctionReportLock::class)->assertEditable($auction);
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

                $isSameAssignment = (int) $auction->assignment_id === (int) $targetAssignmentId;
                if (! $isSameAssignment) {
                    if (! $assignment->is_active) {
                        throw ValidationException::withMessages([
                            'assignment_id' => 'الإسناد المختار غير نشط ولا يمكن ربطه بالمزاد.',
                        ]);
                    }

                    $today = now()->startOfDay();
                    if ($assignment->end_date && $assignment->end_date->startOfDay()->lt($today)) {
                        throw ValidationException::withMessages([
                            'assignment_id' => 'الإسناد المختار منتهي الصلاحية بتاريخ '.system_date($assignment->end_date).'.',
                        ]);
                    }
                }

                $startDate = $data['start_date'] ?? $auction->start_date?->format('Y-m-d');
                $endDate = $data['end_date'] ?? $auction->end_date?->format('Y-m-d');
                if ($startDate && $assignment->start_date && Carbon::parse((string) $startDate)->startOfDay()->lt($assignment->start_date->startOfDay())) {
                    throw ValidationException::withMessages([
                        'start_date' => 'تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد ('.system_date($assignment->start_date).').',
                    ]);
                }
                if ($endDate && $assignment->end_date && Carbon::parse((string) $endDate)->startOfDay()->gt($assignment->end_date->startOfDay())) {
                    throw ValidationException::withMessages([
                        'end_date' => 'تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد ('.system_date($assignment->end_date).').',
                    ]);
                }

                $startTime = $data['start_time'] ?? $auction->start_time;
                $endTime = $data['end_time'] ?? $auction->end_time;
                if ($startDate && $endDate && $startDate === $endDate && $startTime && $endTime) {
                    try {
                        $start = Carbon::createFromFormat('H:i', (string) $startTime);
                        $end = Carbon::createFromFormat('H:i', (string) $endTime);
                        if ($end->lessThanOrEqualTo($start)) {
                            throw ValidationException::withMessages([
                                'end_time' => 'وقت نهاية المزاد يجب أن يكون بعد وقت البداية عندما يكون المزاد في نفس اليوم.',
                            ]);
                        }
                    } catch (Throwable $e) {
                        if ($e instanceof ValidationException) {
                            throw $e;
                        }
                    }
                }

                $propertiesData = (array) ($data['properties_data'] ?? []);
                $auctioneer = (array) ($data['auctioneer'] ?? []);
                $funds = (array) ($data['funds'] ?? []);
                $disclosures = (array) ($data['disclosures'] ?? []);
                $awardPeriod = (int) ($data['award_period'] ?? 30);
                $venue = $data['venue'] ?? null;
                $channels = (array) ($data['channels'] ?? []);
                $campaigns = (array) ($data['campaigns'] ?? []);

                $currentProperties = $auction->properties()->get();
                $propertyIds = array_key_exists('property_ids', $data)
                    ? $this->reportInputs->validatePropertyIds((array) $data['property_ids'], $targetAssignmentId)
                    : $currentProperties->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                $currentPropertyIds = $currentProperties->modelKeys();
                if ($auction->establishmentRequest?->isApproved() && (! $isSameAssignment
                    || array_diff($currentPropertyIds, $propertyIds) !== [] || array_diff($propertyIds, $currentPropertyIds) !== [])) {
                    throw ValidationException::withMessages(['property_ids' => 'لا يمكن تغيير الإسناد أو عقارات المزاد بعد اعتماد طلب إقامته.']);
                }
                $syncData = $this->reportInputs->pivotData(
                    $propertyIds,
                    (array) ($data['property_results'] ?? []),
                    $currentProperties,
                );

                foreach ($propertiesData as $propId => $pRow) {
                    $pId = (int) $propId;
                    if (isset($syncData[$pId])) {
                        $syncData[$pId]['opening_price'] = isset($pRow['opening_value']) && is_numeric($pRow['opening_value']) ? (float) $pRow['opening_value'] : null;
                        $syncData[$pId]['entry_amount'] = isset($pRow['entry_amount']) && is_numeric($pRow['entry_amount']) ? (float) $pRow['entry_amount'] : null;
                        $syncData[$pId]['ownership_share'] = isset($pRow['ownership_share']) && is_numeric($pRow['ownership_share']) ? (float) $pRow['ownership_share'] : 100.0;
                        $syncData[$pId]['disclosure'] = (string) ($pRow['disclosure'] ?? '');
                        $syncData[$pId]['board_data'] = json_encode([
                            'count' => (int) ($pRow['board_count'] ?? 1),
                            'dimensions' => (string) ($pRow['board_dimensions'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }

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
                } elseif (! empty($data['remove_cover_image'])) {
                    if ($auction->cover_image) {
                        $pathsToDelete[] = $auction->cover_image;
                    }
                    $data['cover_image'] = null;
                }

                if ($reportImage instanceof UploadedFile) {
                    $reportImagePath = $reportImage->store('auctions/report-images', 'tenant_public');
                    $storedPaths[] = $reportImagePath;
                    $data['report_image'] = $reportImagePath;
                    if ($auction->report_image) {
                        $pathsToDelete[] = $auction->report_image;
                    }
                } elseif (! empty($data['remove_report_image'])) {
                    if ($auction->report_image) {
                        $pathsToDelete[] = $auction->report_image;
                    }
                    $data['report_image'] = null;
                }

                $removedAttachmentIds = array_values(array_unique(array_map('intval', (array) ($data['remove_attachments'] ?? []))));
                if ($removedAttachmentIds !== []) {
                    $toRemove = $auction->attachments()
                        ->where('category', 'auction_doc')
                        ->whereKey($removedAttachmentIds)
                        ->get();
                    if ($toRemove->count() !== count($removedAttachmentIds)) {
                        throw ValidationException::withMessages(['remove_attachments' => 'المرفقات المحددة لا تخص هذا المزاد.']);
                    }
                    $pathsToDelete = array_merge($pathsToDelete, $toRemove->pluck('file_path')->filter()->all());
                    $auction->attachments()->whereKey($removedAttachmentIds)->delete();
                }

                foreach (['brochure' => 'remove_brochure_evidence', 'platform' => 'remove_platform_evidence'] as $section => $removeKey) {
                    $removeIds = array_values(array_unique(array_map('intval', (array) ($data[$removeKey] ?? []))));
                    if ($removeIds !== []) {
                        $toRemove = $auction->attachments()
                            ->where('report_section', $section)
                            ->whereNull('marketing_campaign_id')
                            ->whereKey($removeIds)
                            ->get();
                        if ($toRemove->count() !== count($removeIds)) {
                            throw ValidationException::withMessages([$removeKey => 'المرفقات المحددة لا تخص هذا المزاد.']);
                        }
                        $pathsToDelete = array_merge($pathsToDelete, $toRemove->pluck('file_path')->filter()->all());
                        $auction->attachments()->whereKey($removeIds)->delete();
                    }
                }

                $brokerPlan = null;
                if (array_key_exists('marketing', $data)) {
                    $allowedMarketingKeys = [
                        'introduction',
                        'report_summary',
                        'planning_methodology',
                        'city_context',
                        'brochure_methodology',
                        'audience_methodology',
                        'messages_methodology',
                        'brochure_context',
                        'brochure_url',
                        'board_context',
                        'board_url',
                        'contract_text',
                        'brokers',
                    ];
                    $incomingMarketing = array_intersect_key($data['marketing'] ?? [], array_flip($allowedMarketingKeys));
                    if (array_key_exists('city_context', $incomingMarketing)) {
                        $incomingMarketing['city_context'] = is_array($incomingMarketing['city_context'])
                            ? array_values(array_unique(array_filter(array_map(
                                static fn (mixed $v): string => trim((string) $v),
                                $incomingMarketing['city_context']
                            ), static fn (string $v): bool => $v !== '')))
                            : [];
                    }
                    if (array_key_exists('brokers', $incomingMarketing) || $brokerEvidence !== []) {
                        $brokerPlan = app(AuctionBrokersManager::class)->plan(
                            $auction,
                            (array) ($incomingMarketing['brokers'] ?? []),
                            $brokerEvidence,
                            'marketing.brokers'
                        );
                        $incomingMarketing['brokers'] = $brokerPlan['brokers'];
                    }
                    $data['marketing_data'] = array_replace($auction->marketing_data ?? [], $incomingMarketing);
                }
                $evidenceMetadata = (array) ($data['marketing_evidence_meta'] ?? []);
                $retainedEvidenceMetadata = (array) ($data['report_attachments'] ?? []);

                unset(
                    $data['property_ids'],
                    $data['property_results'],
                    $data['properties_data'],
                    $data['auctioneer'],
                    $data['funds'],
                    $data['disclosures'],
                    $data['award_period'],
                    $data['venue'],
                    $data['channels'],
                    $data['campaigns'],
                    $data['marketing'],
                    $data['attachments'],
                    $data['remove_cover_image'],
                    $data['remove_report_image'],
                    $data['remove_attachments'],
                    $data['brochure_evidence'],
                    $data['remove_brochure_evidence'],
                    $data['platform_evidence'],
                    $data['remove_platform_evidence'],
                    $data['broker_evidence'],
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
                );

                unset($data['status']);

                $auction->update($data);
                $auction->properties()->sync($syncData);

                if ($removedPropertyIds !== []) {
                    Property::query()->whereIn('id', $removedPropertyIds)->where('status', 'assigned_to_auction')->update(['status' => 'available']);
                }

                foreach ($syncData as $pId => $pData) {
                    $resultStatus = $pData['result_status'] ?? 'pending';
                    if ($resultStatus === 'sold') {
                        Property::query()->where('id', $pId)->update(['status' => 'sold']);
                    } elseif ($resultStatus === 'unsold') {
                        Property::query()->where('id', $pId)->update(['status' => 'available']);
                    } else {
                        Property::query()->where('id', $pId)->where('status', '!=', 'sold')->update(['status' => 'assigned_to_auction']);
                    }
                }

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

                foreach (['brochure' => $brochureEvidence, 'platform' => $platformEvidence] as $section => $sectionFiles) {
                    $offset = (int) $auction->attachments()->where('report_section', $section)->max('display_order') + 1;
                    foreach (array_values($sectionFiles) as $idx => $file) {
                        if (! $file instanceof UploadedFile) {
                            continue;
                        }
                        $path = $file->store('auctions/report-evidence', 'tenant_public');
                        if ($path === false) {
                            throw new RuntimeException('Unable to store auction marketing evidence.');
                        }
                        $storedPaths[] = $path;
                        AuctionAttachment::query()->create([
                            'auction_id' => $auction->id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'category' => 'marketing',
                            'report_section' => $section,
                            'report_metadata' => [
                                'channel' => $section,
                                'kind' => $file->getMimeType() === 'application/pdf' ? 'document' : 'screenshot',
                            ],
                            'display_order' => $offset + $idx,
                            'channels' => ['report'],
                        ]);
                    }
                }

                if ($brokerPlan !== null) {
                    if ($brokerPlan['remove_ids'] !== []) {
                        $removedBrokerFiles = $auction->attachments()
                            ->where('report_section', 'brokers')
                            ->whereKey($brokerPlan['remove_ids'])
                            ->get();
                        $pathsToDelete = array_merge($pathsToDelete, $removedBrokerFiles->pluck('file_path')->filter()->all());
                        $auction->attachments()->whereKey($brokerPlan['remove_ids'])->delete();
                    }
                    foreach ($brokerPlan['existing'] as $attachmentId => $entry) {
                        $auction->attachments()->whereKey($attachmentId)->update([
                            'report_section' => 'brokers',
                            'report_metadata' => $entry['metadata'],
                            'display_order' => $entry['order'],
                        ]);
                    }
                    foreach ($brokerPlan['uploads'] as $entry) {
                        /** @var UploadedFile $file */
                        $file = $entry['file'];
                        $path = $file->store('auctions/report-evidence', 'tenant_public');
                        if ($path === false) {
                            throw new RuntimeException('Unable to store broker evidence.');
                        }
                        $storedPaths[] = $path;
                        AuctionAttachment::query()->create([
                            'auction_id' => $auction->id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'category' => 'marketing',
                            'report_section' => 'brokers',
                            'report_metadata' => $entry['metadata'],
                            'display_order' => $entry['order'],
                            'channels' => ['report'],
                        ]);
                    }
                }

                foreach ($propertiesData as $propId => $pRow) {
                    $pId = (int) $propId;
                    $valFile = (isset($pRow['valuation']) && $pRow['valuation'] instanceof UploadedFile)
                        ? $pRow['valuation']
                        : request()->file("properties_data.{$propId}.valuation");
                    if ($valFile instanceof UploadedFile) {
                        $oldVal = $auction->attachments()->where('category', 'valuation')->where('property_id', $pId)->get();
                        $pathsToDelete = array_merge($pathsToDelete, $oldVal->pluck('file_path')->filter()->all());
                        $auction->attachments()->where('category', 'valuation')->where('property_id', $pId)->delete();

                        $path = $valFile->store('auctions/valuations', 'tenant_public');
                        $storedPaths[] = $path;
                        AuctionAttachment::query()->create([
                            'auction_id' => $auction->id,
                            'property_id' => $pId,
                            'file_path' => $path,
                            'file_name' => $valFile->getClientOriginalName(),
                            'file_type' => $valFile->getClientOriginalExtension(),
                            'file_size' => $valFile->getSize(),
                            'category' => 'valuation',
                            'channels' => ['internal'],
                        ]);
                    }

                    $bFiles = (isset($pRow['board_before']) && is_array($pRow['board_before']))
                        ? $pRow['board_before']
                        : (request()->file("properties_data.{$propId}.board_before") ?? []);
                    if ($bFiles instanceof UploadedFile) {
                        $bFiles = [$bFiles];
                    }
                    if (is_array($bFiles)) {
                        foreach ($bFiles as $bFile) {
                            if ($bFile instanceof UploadedFile) {
                                $path = $bFile->store('auctions/report-evidence', 'tenant_public');
                                $storedPaths[] = $path;
                                AuctionAttachment::query()->create([
                                    'auction_id' => $auction->id,
                                    'property_id' => $pId,
                                    'file_path' => $path,
                                    'file_name' => $bFile->getClientOriginalName(),
                                    'file_type' => $bFile->getClientOriginalExtension(),
                                    'file_size' => $bFile->getSize(),
                                    'category' => 'marketing',
                                    'report_section' => 'board_before',
                                    'report_metadata' => [
                                        'channel' => 'board_before',
                                        'kind' => 'photo',
                                    ],
                                    'channels' => ['report'],
                                ]);
                            }
                        }
                    }
                }

                if ($auctioneer !== [] || $funds !== [] || $disclosures !== []) {
                    try {
                        $schema = app(AuctionEstablishmentRequestData::class);
                        $estInput = [
                            'auctioneer' => $auctioneer,
                            'auction' => [
                                'award_period' => $awardPeriod,
                                'venue' => $venue ?? '',
                            ],
                            'funds' => $funds,
                            'disclosures' => $disclosures,
                            'properties' => [],
                        ];
                        foreach ($propertiesData as $pId => $pRow) {
                            $estInput['properties'][] = [
                                'property_id' => (int) $pId,
                                'opening_value' => $pRow['opening_value'] ?? 0,
                                'entry_amount' => $pRow['entry_amount'] ?? 0,
                                'ownership_share' => $pRow['ownership_share'] ?? 100,
                                'disclosure' => $pRow['disclosure'] ?? '',
                                'board_count' => $pRow['board_count'] ?? 1,
                                'board_dimensions' => $pRow['board_dimensions'] ?? '',
                            ];
                        }
                        $snapshotData = $schema->snapshot($auction->fresh(['properties.owners', 'properties.assignment', 'assignment']), $estInput);
                        AuctionEstablishmentRequest::query()->updateOrCreate(
                            ['auction_id' => $auction->id],
                            [
                                'data' => $snapshotData,
                                'submitted_at' => now(),
                                'updated_by' => auth('tenant')->id(),
                            ]
                        );
                    } catch (Throwable $estEx) {
                        report($estEx);
                    }
                }

                if ($channels !== [] || $campaigns !== []) {
                    try {
                        app(SaveMarketingCampaignAction::class)->executeBatch(
                            $auction,
                            [
                                'channels' => $channels,
                                'campaigns' => $campaigns,
                            ],
                            auth('tenant')->id() ? (int) auth('tenant')->id() : null,
                            $campaigns
                        );
                    } catch (Throwable $campEx) {
                        report($campEx);
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
                $this->reportInputs->syncEvidenceMetadata($auction, $retainedEvidenceMetadata);

                return $auction->fresh(['properties', 'attachments', 'assignment']);
            });
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }

        if ($pathsToDelete !== []) {
            $uniquePathsToDelete = array_values(array_unique($pathsToDelete));
            DB::afterCommit(static fn () => Storage::disk('tenant_public')->delete($uniquePathsToDelete));
        }

        if (tenancy()->initialized && tenant('id')) {
            $this->reportService->requestGeneration($updated, (string) tenant('id'));
        }

        return $updated;
    }
}
