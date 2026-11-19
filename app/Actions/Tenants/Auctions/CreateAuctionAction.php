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
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class CreateAuctionAction
{
    public function __construct(
        private readonly AuctionReportInputService $reportInputs,
        private readonly AuctionReportService $reportService,
    ) {}

    /**
     * @param  array<string, mixed>|User  $userOrData
     * @param  array<string, mixed>|int|null  $dataOrCreatorId
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int, UploadedFile>  $marketingEvidence
     * @param  array<int|string, array<int, UploadedFile>>  $boardImagesBefore
     * @param  array<int|string, array<int, UploadedFile>>  $boardImagesAfter
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
        array $brochureEvidence = [],
        array $platformEvidence = [],
        array $brokerEvidence = [],
    ): Auction {
        if (count($brochureEvidence) > 1) {
            throw ValidationException::withMessages(['brochure_evidence' => 'يمكن إرفاق ملف واحد فقط للكتيب الإلكتروني.']);
        }
        if ($userOrData instanceof User) {
            $user = $userOrData;
            $data = (array) $dataOrCreatorId;
            $creatorId = $user->id;
        } else {
            $data = (array) $userOrData;
            $creatorId = is_int($dataOrCreatorId) ? $dataOrCreatorId : null;
        }

        if (! empty($data['property_results'])) {
            throw ValidationException::withMessages(['auction' => 'تُضاف نتائج الإقفال بعد انتهاء المزاد من صفحة تفاصيل المزاد.']);
        }
        $storedPaths = [];

        try {
            $auction = DB::transaction(function () use ($data, $creatorId, $coverImage, $attachments, $reportImage, $marketingEvidence, $boardImagesBefore, $boardImagesAfter, $brochureEvidence, $platformEvidence, $brokerEvidence, &$storedPaths): Auction {
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
                        'assignment_id' => 'الإسناد المختار منتهي الصلاحية بتاريخ '.system_date($assignment->end_date).'.',
                    ]);
                }

                $startDate = $data['start_date'] ?? null;
                $endDate = $data['end_date'] ?? null;
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

                $propertyIds = $this->reportInputs->validatePropertyIds((array) ($data['property_ids'] ?? []), $assignmentId);
                $pivotData = $this->reportInputs->pivotData($propertyIds, (array) ($data['property_results'] ?? []));

                foreach ($propertiesData as $propId => $pRow) {
                    $pId = (int) $propId;
                    if (isset($pivotData[$pId])) {
                        $pivotData[$pId]['opening_price'] = isset($pRow['opening_value']) && is_numeric($pRow['opening_value']) ? (float) $pRow['opening_value'] : null;
                        $pivotData[$pId]['entry_amount'] = isset($pRow['entry_amount']) && is_numeric($pRow['entry_amount']) ? (float) $pRow['entry_amount'] : null;
                        $pivotData[$pId]['ownership_share'] = isset($pRow['ownership_share']) && is_numeric($pRow['ownership_share']) ? (float) $pRow['ownership_share'] : 100.0;
                        $pivotData[$pId]['disclosure'] = (string) ($pRow['disclosure'] ?? '');
                        $pivotData[$pId]['board_data'] = json_encode([
                            'count' => (int) ($pRow['board_count'] ?? 1),
                            'dimensions' => (string) ($pRow['board_dimensions'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }

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
                $marketingPayload = is_array($data['marketing'] ?? null) ? $data['marketing'] : [];
                $brokerPlan = null;
                if (array_key_exists('brokers', $marketingPayload) || $brokerEvidence !== []) {
                    $brokerPlan = app(AuctionBrokersManager::class)->plan(null, (array) ($marketingPayload['brokers'] ?? []), $brokerEvidence, 'marketing.brokers');
                    $marketingPayload['brokers'] = $brokerPlan['brokers'];
                }
                if (isset($marketingPayload['city_context']) && is_array($marketingPayload['city_context'])) {
                    $marketingPayload['city_context'] = array_values(array_unique(array_filter(array_map(
                        static fn (mixed $v): string => trim((string) $v),
                        $marketingPayload['city_context']
                    ), static fn (string $v): bool => $v !== '')));
                }
                $data['marketing_data'] = $marketingPayload !== [] ? $marketingPayload : null;
                $evidenceMetadata = (array) ($data['marketing_evidence_meta'] ?? []);

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
                    $data['marketing_evidence'],
                    $data['marketing_evidence_meta'],
                    $data['report_attachments'],
                    $data['board_images_before'],
                    $data['board_images_after'],
                    $data['brochure_evidence'],
                    $data['platform_evidence'],
                    $data['broker_evidence'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key'],
                    $data['_wizard_step'],
                    $data['fields'],
                    $data['target_path'],
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
                        Property::query()->where('id', $pId)->update(['status' => 'sold']);
                    } elseif ($resultStatus === 'unsold') {
                        Property::query()->where('id', $pId)->update(['status' => 'available']);
                    } else {
                        Property::query()->where('id', $pId)->where('status', '!=', 'sold')->update(['status' => 'assigned_to_auction']);
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

                foreach (['brochure' => $brochureEvidence, 'platform' => $platformEvidence] as $section => $sectionFiles) {
                    foreach (array_values($sectionFiles) as $order => $file) {
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
                            'display_order' => $order,
                            'channels' => ['report'],
                        ]);
                    }
                }

                if ($brokerPlan !== null) {
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
                                'created_by' => $creator,
                                'updated_by' => $creator,
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
                            $creator,
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

                $this->reportInputs->syncBrochureUrl($auction);

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
