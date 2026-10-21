<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuctionTemplateVersion extends Model
{
    protected $fillable = [
        'auction_template_id', 'version', 'schema', 'status', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuctionTemplate::class, 'auction_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}