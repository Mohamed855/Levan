<?php

declare(strict_types=1);

namespace App\Models\Admin\Subscriptions;

use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Subscription extends Model
{
    protected $fillable = [
        'subscription_number', 'tenant_id', 'plan_id', 'billing_cycle', 'status', 'price',
        'total_users', 'used_users', 'auto_renew', 'starts_at', 'ends_at', 'next_payment_date',
        'cancelled_at', 'cancellation_reason', 'trial_end_date', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'auto_renew' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'next_payment_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'trial_end_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function periods(): HasMany { return $this->hasMany(SubscriptionPeriod::class); }
    public function currentPeriod(): HasOne { return $this->hasOne(SubscriptionPeriod::class)->where('is_current', true)->latestOfMany('period_start'); }
}
