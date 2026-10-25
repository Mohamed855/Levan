<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenants\Properties\Property;

class AuctionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'category',
        'report_section',
        'property_id',
        'display_order',
        'channels',
    ];

    protected $casts = [
        'channels' => 'array',
        'file_size' => 'integer',
        'property_id' => 'integer',
        'display_order' => 'integer',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'auction_doc' => 'وثيقة المزاد',
            'marketing' => 'مواد تسويقية',
            'property_images' => 'صور العقارات',
            'approval_doc' => 'وثيقة اعتماد',
            'internal_only' => 'ملف داخلي',
            default => 'ملف مرفق',
        };
    }
}
