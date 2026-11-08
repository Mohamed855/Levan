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

final class DeleteMarketingCampaignAction
{
    public function execute(MarketingCampaign $campaign): void
    {
        $paths = DB::transaction(function () use ($campaign): array {
            $auction = Auction::query()->lockForUpdate()->findOrFail($campaign->auction_id);
            app(AuctionReportLock::class)->assertEditable($auction);
            $record = MarketingCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if ((int) $record->auction_id !== (int) $campaign->auction_id) {
                throw ValidationException::withMessages(['campaign' => 'تغير المزاد المرتبط بالحملة. أعد تحميل الصفحة قبل الحذف.']);
            }
            $paths = $record->evidence()->pluck('file_path')->all();
            $record->evidence()->delete();
            $record->delete();

            return $paths;
        });
        app(AuctionReportRefreshService::class)->afterMutation([$campaign->auction_id]);
        if ($paths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_public')->delete($paths));
        }
    }
}
