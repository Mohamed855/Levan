<?php

declare(strict_types=1);

namespace App\Models\Tenants\AuctionClosings;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionClosing extends Model
{
    protected $fillable = ['auction_id', 'closing_date', 'approval_number', 'saved_at', 'finalized_snapshot', 'created_by', 'updated_by'];

    protected $casts = ['closing_date' => 'date', 'saved_at' => 'datetime', 'finalized_snapshot' => 'array'];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }
}
