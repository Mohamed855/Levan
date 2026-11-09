<?php

declare(strict_types=1);

namespace App\Models\Tenants\MarketingCampaigns;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MarketingCampaign extends Model
{
    protected $table = 'auction_marketing_campaigns';

    protected $fillable = ['auction_id', 'channel', 'campaign_date', 'platform_label', 'metrics', 'url', 'link_label', 'link_role', 'created_by', 'legacy_key'];

    protected $casts = ['metrics' => 'array', 'campaign_date' => 'date'];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(AuctionAttachment::class, 'marketing_campaign_id')->orderBy('display_order')->orderBy('id');
    }

    public function getChannelLabelAttribute(): string
    {
        if ($this->platform_label) {
            return $this->platform_label;
        }

        $labels = __('auction_reports.channels', [], 'ar');

        return (is_array($labels) ? ($labels[$this->channel] ?? null) : null)
            ?: $this->channel;
    }

    public function reportData(): array
    {
        return ['id' => (int) $this->id, 'platform' => $this->channel, 'campaign_date' => $this->campaign_date?->format('Y-m-d'), 'platform_label' => $this->platform_label, 'url' => $this->url, 'link_label' => $this->link_label, 'link_role' => $this->link_role] + ($this->metrics ?? []);
    }
}
