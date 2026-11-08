<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Validation\ValidationException;

final class AuctionReportLock
{
    public function assertEditable(Auction $auction): void
    {
        $auction->unsetRelation('closing');
        if ((new AuctionReportState)->forAuction($auction) === AuctionReportContentStatus::ENDED) {
            throw ValidationException::withMessages(['auction' => 'المزاد منتهٍ ولا يمكن تعديل بياناته أو حملاته أو إقفاله. يمكنك إعادة توليد التقريرين من التفاصيل.']);
        }
    }
}
