<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LicenseSeat extends Model
{
    protected $fillable = [
        'tenant_id', 'subscription_id', 'assigned_user_id', 'status', 'assigned_at', 'is_core', 'metadata',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'is_core' => 'boolean', 'metadata' => 'array'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
