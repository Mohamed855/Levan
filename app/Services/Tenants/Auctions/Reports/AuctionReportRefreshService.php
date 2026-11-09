<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Support\Facades\DB;

final class AuctionReportRefreshService
{
    public function afterMutation(array $auctionIds, bool $includeFinalized = false): void
    {
        foreach (array_unique(array_map('intval', $auctionIds)) as $id) {
            DB::transaction(function () use ($id, $includeFinalized): void {
                $auction = Auction::query()->whereKey($id)->lockForUpdate()->first();
                if (! $auction || (! $includeFinalized && $auction->isClosed())) {
                    return;
                }
                $snapshot = app(AuctionReportDataBuilder::class)->build($auction->fresh());
                $previous = $auction->latestReportGeneration()->first()?->snapshot;
                if ($previous && $this->businessData($previous) === $this->businessData($snapshot)) {
                    return;
                }
                app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'));
            });
        }
    }

    private function businessData(array $snapshot): array
    {
        unset($snapshot['generated_at'], $snapshot['branding']['company_logo'], $snapshot['marketing']['contacts']);

        return $snapshot;
    }
}
