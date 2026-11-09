<?php

declare(strict_types=1);

namespace App\Models\Tenants\AuctionEstablishments;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionEstablishmentRequest extends Model
{
    protected $fillable = ['auction_id', 'data', 'submitted_at', 'created_by', 'updated_by'];

    protected $casts = ['data' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null && trim((string) $this->approval_number) !== '';
    }

    protected function status(): Attribute
    {
        return Attribute::get(fn (): string => $this->isApproved() ? 'approved' : 'under_review');
    }
}
