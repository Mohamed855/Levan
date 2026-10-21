<?php

declare(strict_types=1);

namespace App\Models\Admin\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SubscriptionPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id', 'period_number', 'period_start', 'period_end', 'plan_id_snapshot',
        'plan_code_snapshot', 'plan_name_snapshot', 'plan_type_snapshot', 'billing_cycle_snapshot',
        'limits_mode_snapshot', 'limits_snapshot', 'features_snapshot', 'price_snapshot',
        'total_users_snapshot', 'used_users_snapshot', 'status', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime', 'period_end' => 'datetime', 'is_current' => 'boolean',
            'limits_snapshot' => 'array', 'features_snapshot' => 'array', 'price_snapshot' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
