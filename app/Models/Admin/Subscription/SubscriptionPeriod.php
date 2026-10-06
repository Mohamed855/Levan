<?php

declare(strict_types=1);

namespace App\Models\Admin\Subscription;

use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPeriod extends Model
{
    use SoftDeletes, HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'period_number',
        'subscription_id',
        'period_start',
        'period_end',
        'plan_id_snapshot',
        'plan_code_snapshot',
        'plan_name_snapshot',
        'plan_type_snapshot',
        'billing_cycle_snapshot',
        'limits_mode_snapshot',
        'limits_snapshot',
        'features_snapshot',
        'price_snapshot',
        'total_users_snapshot',
        'used_users_snapshot',
        'status',
        'is_current',
        'reminded_at_30',
        'reminded_at_7',
    ];

    protected $casts = [
        'status'                 => SubscriptionPeriodStatus::class,
        'period_start'           => 'datetime',
        'period_end'             => 'datetime',
        'amount'                 => 'decimal:2',
        'is_current'             => 'boolean',
        'price_snapshot'         => 'decimal:2',
        'total_users_snapshot'   => 'integer',
        'used_users_snapshot'    => 'integer',
        'limits_snapshot'        => 'array',
        'features_snapshot'      => 'array',
        'reminded_at_30'         => 'datetime',
        'reminded_at_7'          => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($period) {
            if (!$period->period_number) {
                $max = self::where('subscription_id', $period->subscription_id)->max('period_number');
                $period->period_number = (int) $max + 1;
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function licenseAdjustments(): HasMany
    {
        return $this->hasMany(SubscriptionLicenseAdjustment::class, 'period_id');
    }

    public function isUnlimited(): bool
    {
        return (string) $this->limits_mode_snapshot === 'unlimited';
    }

    public function getLimits(): array
    {
        if ($this->isUnlimited()) {
            return [
                'included_users'        => null,
                'properties_limit'      => null,
                'storage_quota_gb'      => null,
                'active_auctions_limit' => null,
            ];
        }

        return (array) ($this->limits_snapshot ?? []);
    }
}
