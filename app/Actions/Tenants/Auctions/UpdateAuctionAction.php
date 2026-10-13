<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;
use Illuminate\Http\UploadedFile;

final class UpdateAuctionAction
{
    public function __construct(private readonly AuctionService $auctionService) {}

    /** @param array<int, UploadedFile> $attachments */
    public function execute(
        Auction $auction,
        array $data,
        ?UploadedFile $coverImage = null,
        array $attachments = [],
    ): Auction {
        return $this->auctionService->updateAuction($auction, $data, $coverImage, $attachments);
    }
}
