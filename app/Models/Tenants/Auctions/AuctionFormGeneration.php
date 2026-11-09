<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class AuctionFormGeneration extends Model
{
    protected $fillable = [
        'auction_id', 'type', 'revision_token', 'status', 'progress', 'snapshot',
        'file_path', 'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'progress' => 'integer', 'snapshot' => 'array',
        'started_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $generation): void {
            if ($generation->isDirty(['auction_id', 'type', 'revision_token', 'snapshot'])) {
                throw new LogicException('Regulatory form generation snapshots are immutable.');
            }
        });
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && filled($this->file_path);
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['queued', 'processing'], true);
    }

    public function publicErrorMessage(): ?string
    {
        return $this->status === 'failed' ? 'تعذر تجهيز النموذج. يرجى إعادة المحاولة.' : null;
    }
}
