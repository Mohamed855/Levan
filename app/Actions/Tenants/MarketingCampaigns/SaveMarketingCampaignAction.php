<?php

declare(strict_types=1);

namespace App\Actions\Tenants\MarketingCampaigns;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class SaveMarketingCampaignAction
{
    public function execute(?MarketingCampaign $campaign, array $data, ?int $actorId, array $files = [], array $removeEvidenceIds = []): MarketingCampaign
    {
        if (isset($data['channels']) || isset($data['campaigns'])) {
            $auction = Auction::findOrFail((int) $data['auction_id']);
            $saved = $this->executeBatch($auction, $data, $actorId, $files);

            return $saved[0] ?? new MarketingCampaign(['auction_id' => $auction->id]);
        }

        $stored = [];
        $removedPaths = [];
        $affected = array_filter([$campaign?->auction_id, (int) $data['auction_id']]);
        try {
            $saved = DB::transaction(function () use ($campaign, $data, $actorId, $files, $removeEvidenceIds, $affected, &$stored, &$removedPaths): MarketingCampaign {
                foreach (Auction::whereIn('id', $affected)->orderBy('id')->lockForUpdate()->get() as $auction) {
                    app(AuctionReportLock::class)->assertEditable($auction);
                }
                $auction = Auction::findOrFail($data['auction_id']);
                $record = $campaign ? MarketingCampaign::query()->lockForUpdate()->findOrFail($campaign->id) : new MarketingCampaign;
                if ($record->exists && (int) $record->auction_id !== (int) $campaign->auction_id) {
                    throw ValidationException::withMessages(['auction_id' => 'تغير المزاد المرتبط بالحملة. أعد تحميل الصفحة قبل الحفظ.']);
                }

                $metrics = is_array($data['metrics'] ?? null) ? $data['metrics'] : [];
                if (($data['channel'] ?? '') === 'whatsapp') {
                    $metrics = $this->withComputedWhatsappTotal($metrics);
                }
                $data['metrics'] = $metrics;

                $record->fill(array_intersect_key($data, array_flip(['auction_id', 'channel', 'campaign_date', 'platform_label', 'metrics', 'url', 'link_label', 'link_role'])));
                $record->platform_label = null;
                $record->link_role = match ($record->channel) {
                    'youtube' => 'video', 'marketing_evidence' => 'marketing_evidence', default => null,
                };
                if (! in_array($record->channel, config('auction-marketing-fields.links'), true)) {
                    $record->url = null;
                }
                if ($record->channel !== 'whatsapp') {
                    $record->campaign_date = null;
                }
                if (! $record->exists) {
                    $record->created_by = $actorId;
                }
                $record->save();

                if (array_key_exists('social_context', $data) && $data['social_context'] !== null) {
                    $marketing = is_array($auction->marketing_data) ? $auction->marketing_data : [];
                    $marketing['social_context'] = $data['social_context'];
                    $auction->update(['marketing_data' => $marketing]);
                }

                if ($removeEvidenceIds !== []) {
                    $evidence = $record->evidence()->whereKey($removeEvidenceIds)->get();
                    if ($evidence->count() !== count(array_unique($removeEvidenceIds))) {
                        throw ValidationException::withMessages(['remove_evidence' => 'المرفقات المحددة لا تخص هذه الحملة.']);
                    }
                    $removedPaths = $evidence->pluck('file_path')->all();
                    $record->evidence()->whereKey($removeEvidenceIds)->delete();
                }

                $record->evidence()->update(['auction_id' => $record->auction_id, 'property_id' => null]);
                $section = $this->section($record->channel);
                if ($record->wasChanged('channel')) {
                    foreach ($record->evidence()->get() as $evidence) {
                        $evidence->update(['report_section' => $section, 'report_metadata' => array_replace($evidence->report_metadata ?? [], ['channel' => $record->channel])]);
                    }
                }

                foreach ($files as $index => $file) {
                    $path = $file->store('auctions/report-evidence', 'tenant_public');
                    if ($path === false) {
                        throw new RuntimeException('Unable to store campaign evidence.');
                    }
                    $stored[] = $path;
                    $record->evidence()->create([
                        'auction_id' => $record->auction_id,
                        'property_id' => null,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'category' => 'marketing',
                        'report_section' => $section,
                        'report_metadata' => [
                            'channel' => $record->channel,
                            'kind' => $file->getMimeType() === 'application/pdf' ? 'document' : 'screenshot',
                        ],
                        'display_order' => $index,
                        'channels' => ['report'],
                    ]);
                }

                return $record;
            });
        } catch (Throwable $error) {
            Storage::disk('tenant_public')->delete($stored);
            throw $error;
        }
        app(AuctionReportRefreshService::class)->afterMutation($affected);
        if ($removedPaths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_public')->delete($removedPaths));
        }

        return $saved;
    }

    /**
     * Save multiple marketing campaigns for an auction.
     *
     * @return array<MarketingCampaign>
     */
    public function executeBatch(Auction $auction, array $data, ?int $actorId, array $filesByChannel = []): array
    {
        $stored = [];
        $removedPaths = [];
        $auctionId = (int) $auction->id;

        try {
            $savedCampaigns = DB::transaction(function () use ($auction, $data, $actorId, $filesByChannel, &$stored, &$removedPaths): array {
                $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);
                app(AuctionReportLock::class)->assertEditable($auction);

                if (array_key_exists('social_context', $data)) {
                    $marketing = is_array($auction->marketing_data) ? $auction->marketing_data : [];
                    $marketing['social_context'] = $data['social_context'];
                    $auction->update(['marketing_data' => $marketing]);
                }

                $channels = (array) ($data['channels'] ?? array_keys($data['campaigns'] ?? []));
                $campaignsData = (array) ($data['campaigns'] ?? []);
                $results = [];

                foreach ($channels as $channel) {
                    if (! is_string($channel) || ! array_key_exists($channel, config('auction-marketing-campaigns'))) {
                        continue;
                    }

                    $chData = (array) ($campaignsData[$channel] ?? []);
                    $metrics = is_array($chData['metrics'] ?? null) ? $chData['metrics'] : [];
                    if ($channel === 'whatsapp') {
                        $metrics = $this->withComputedWhatsappTotal($metrics);
                    }

                    $record = MarketingCampaign::where('auction_id', $auction->id)->where('channel', $channel)->first();
                    $record ??= new MarketingCampaign;
                    $chFiles = $filesByChannel[$channel]['evidence'] ?? [];
                    if (! is_array($chFiles) && $chFiles) {
                        $chFiles = [$chFiles];
                    }

                    $record->auction_id = $auction->id;
                    $record->channel = $channel;
                    $record->campaign_date = ($channel === 'whatsapp') ? ($chData['campaign_date'] ?? null) : null;
                    $record->url = in_array($channel, config('auction-marketing-fields.links'), true) ? ($chData['url'] ?? null) : null;
                    $record->link_role = match ($channel) {
                        'youtube' => 'video', 'marketing_evidence' => 'marketing_evidence', default => null,
                    };
                    $record->metrics = $metrics;
                    if (! $record->exists) {
                        $record->created_by = $actorId;
                    }
                    $record->save();

                    $removeIds = array_values(array_unique(array_map('intval', (array) ($chData['remove_evidence'] ?? []))));
                    if ($removeIds !== []) {
                        $evidence = $record->evidence()->whereKey($removeIds)->get();
                        if ($evidence->count() !== count($removeIds)) {
                            throw ValidationException::withMessages(["campaigns.{$channel}.remove_evidence" => 'المرفقات المحددة لا تخص هذه الحملة.']);
                        }
                        $removedPaths = array_merge($removedPaths, $evidence->pluck('file_path')->all());
                        $record->evidence()->whereKey($removeIds)->delete();
                    }

                    $record->evidence()->update([
                        'auction_id' => $record->auction_id,
                        'property_id' => null,
                    ]);

                    $section = $this->section($record->channel);

                    foreach ($chFiles as $index => $file) {
                        if (! $file) {
                            continue;
                        }
                        $path = $file->store('auctions/report-evidence', 'tenant_public');
                        if ($path === false) {
                            throw new RuntimeException('Unable to store campaign evidence.');
                        }
                        $stored[] = $path;
                        $record->evidence()->create([
                            'auction_id' => $record->auction_id,
                            'property_id' => null,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'category' => 'marketing',
                            'report_section' => $section,
                            'report_metadata' => [
                                'channel' => $record->channel,
                                'kind' => $file->getMimeType() === 'application/pdf' ? 'document' : 'screenshot',
                            ],
                            'display_order' => $index,
                            'channels' => ['report'],
                        ]);
                    }

                    $results[] = $record;
                }

                return $results;
            });
        } catch (Throwable $error) {
            Storage::disk('tenant_public')->delete($stored);
            throw $error;
        }

        app(AuctionReportRefreshService::class)->afterMutation([$auctionId]);
        if ($removedPaths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_public')->delete($removedPaths));
        }

        return $savedCampaigns;
    }

    /** @param array<string, mixed> $metrics
     * @return array<string, mixed>
     */
    private function withComputedWhatsappTotal(array $metrics): array
    {
        $delivered = (int) ($metrics['delivered'] ?? 0);
        $failed = (int) ($metrics['failed'] ?? 0);
        $pending = (int) ($metrics['pending'] ?? 0);
        $metrics['total'] = $delivered + $failed + $pending;

        return $metrics;
    }

    private function section(string $channel): string
    {
        return match ($channel) {
            'youtube' => 'video',
            'x' => 'x',
            'whatsapp' => 'whatsapp',
            'tiktok', 'snapchat' => 'social',
            default => 'marketing_evidence',
        };
    }
}
