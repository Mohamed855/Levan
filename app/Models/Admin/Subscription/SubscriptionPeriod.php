<?php

namespace App\Models\Admin\Subscription;


use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPeriod extends Model
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use SoftDeletes, HasFactory;

    protected $connection = 'mysql';


    protected $fillable = [
        'period_number',
        'subscription_id',
        'period_start',
        'period_end',
        'plan_id_snapshot',
        'price_snapshot',
        'total_users_snapshot',
        'used_users_snapshot',
        'status',
        'is_current',
        'reminded_at_30',
        'reminded_at_7',
    ];

    protected $casts = [
        'status'         => SubscriptionPeriodStatus::class,
        'period_start'   => 'datetime',
        'period_end'     => 'datetime',
        'amount'         => 'decimal:2',
        'is_current'     => 'boolean',
        'price_snapshot' => 'decimal:2',
        'reminded_at_30' => 'datetime',
        'reminded_at_7'  => 'datetime',
    ];


    /*
    |============================================================================
    |============================================================================
    |                               Booted
    |============================================================================
    |============================================================================
    */
    protected static function booted()
    {
        static::creating(function ($period) {
            if (!$period->period_number) {
                $max = self::where('subscription_id', $period->subscription_id)->max('period_number');
                $period->period_number = (int) $max + 1;
            }
        });
    }


    /*
    |============================================================================
    |============================================================================
    |                               Relations
    |============================================================================
    |============================================================================
    */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function licenseAdjustments()
    {
        return $this->hasMany(SubscriptionLicenseAdjustment::class, 'period_id');
    }
}
