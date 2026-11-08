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
use Throwable;

final class SaveMarketingCampaignAction
{
    public function execute(?MarketingCampaign $campaign, array $data, ?int $actorId, array $files = [], array $removeEvidenceIds = []): MarketingCampaign
    {
        $stored = [];
        $removedPaths = [];
        $affected = array_filter([$campaign?->auction_id, (int) $data['auction_id']]);
        try {
            $saved = DB::transaction(function () use ($campaign, $data, $actorId, $files, $removeEvidenceIds, $affected, &$stored, &$removedPaths): MarketingCampaign {
                foreach (Auction::whereIn('id', $affected)->orderBy('id')->lockForUpdate()->get() as $auction) {
                    app(AuctionReportLock::class)->assertEditable($auction);
                }
                Auction::findOrFail($data['auction_id']);
                if (($data['channel'] ?? '') === 'board_before' && ! Auction::findOrFail($data['auction_id'])->properties()->whereKey($data['metrics']['property_id'] ?? 0)->exists()) {
                    throw ValidationException::withMessages(['metrics.property_id' => 'اختر عقارًا مرتبطًا بالمزاد.']);
                }
                $record = $campaign ? MarketingCampaign::query()->lockForUpdate()->findOrFail($campaign->id) : new MarketingCampaign;
                if ($record->exists && (int) $record->auction_id !== (int) $campaign->auction_id) {
                    throw ValidationException::withMessages(['auction_id' => 'تغير المزاد المرتبط بالحملة. أعد تحميل الصفحة قبل الحفظ.']);
                }
                $record->fill(array_intersect_key($data, array_flip(['auction_id', 'channel', 'campaign_date', 'platform_label', 'metrics', 'url', 'link_label', 'link_role'])));
                if ($record->channel !== 'other') {
                    $record->platform_label = null;
                }
                $record->link_role = match ($record->channel) {
                    'brochure' => 'brochure', 'youtube' => 'video', 'board_before' => 'boards', 'marketing_evidence' => 'marketing_evidence', default => null,
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
                if ($removeEvidenceIds !== []) {
                    $evidence = $record->evidence()->whereKey($removeEvidenceIds)->get();
                    if ($evidence->count() !== count(array_unique($removeEvidenceIds))) {
                        throw ValidationException::withMessages(['remove_evidence' => 'المرفقات المحددة لا تخص هذه الحملة.']);
                    }
                    $removedPaths = $evidence->pluck('file_path')->all();
                    $record->evidence()->whereKey($removeEvidenceIds)->delete();
                }
                $record->evidence()->update(['auction_id' => $record->auction_id, 'property_id' => $record->channel === 'board_before' ? $record->metrics['property_id'] : null]);
                $section = $this->section($record->channel);
                if ($record->wasChanged('channel')) {
                    foreach ($record->evidence()->get() as $evidence) {
                        $evidence->update(['report_section' => $section, 'report_metadata' => array_replace($evidence->report_metadata ?? [], ['channel' => $record->channel])]);
                    }
                }
                foreach ($files as $index => $file) {
                    $path = $file->store('auctions/report-evidence', 'tenant_public');
                    $stored[] = $path;
                    $record->evidence()->create(['auction_id' => $record->auction_id, 'property_id' => $record->channel === 'board_before' ? $record->metrics['property_id'] : null, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'file_type' => $file->getClientOriginalExtension(), 'file_size' => $file->getSize(), 'category' => 'marketing', 'report_section' => $section, 'report_metadata' => ['channel' => $record->channel, 'kind' => $file->getMimeType() === 'application/pdf' ? 'document' : 'screenshot'], 'display_order' => $index, 'channels' => ['report']]);
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

    private function section(string $channel): string
    {
        return match ($channel) {
            'youtube' => 'video', 'x' => 'x', 'whatsapp' => 'whatsapp', 'tiktok', 'snapchat' => 'social', 'brochure', 'platform', 'board_before', 'brokers' => $channel, default => 'marketing_evidence'
        };
    }
}
