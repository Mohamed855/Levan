<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionReportGeneration extends Model
{
    protected $fillable = [
        'auction_id',
        'revision_token',
        'status',
        'snapshot',
        'outputs_path',
        'report_path',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && filled($this->outputs_path) && filled($this->report_path);
    }
}
