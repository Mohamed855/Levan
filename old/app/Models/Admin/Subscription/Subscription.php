<?php

namespace App\Models\Admin\Subscription;

use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'subscription_number',
        'tenant_id',
        'plan_id',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'status',
        'price',
        'total_users',
        'used_users',
        'auto_renew',
        'next_payment_date',
        'cancellation_reason',
        'cancelled_at',
        'metadata',
        'notes',
        'transfer_bank_attachment',

        'trial_end_date',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'billing_cycle' => SubscriptionBillingCycle::class,
        'price' => 'decimal:2',
        'licenses_quota' => 'integer',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
        'next_payment_date' => 'datetime',
        'transfer_bank_attachment' => 'array',
    ];

    /*
    |============================================================================
    |============================================================================
    |                               Boot method
    |============================================================================
    |============================================================================
    */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->subscription_number)) {
                $subscription->subscription_number = static::generateSubscriptionNumber();
            }
        });
    }

    public static function generateSubscriptionNumber(): string
    {
        $year = date('Y');
        $lastSubscription = static::where('subscription_number', 'LIKE', "SUB-{$year}-%")
            ->latest('id')
            ->first();

        $number = 1;
        if ($lastSubscription && preg_match('/SUB-\d{4}-(\d+)/', $lastSubscription->subscription_number, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        do {
            $candidate = 'SUB-'.$year.'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
            $number++;
        } while (static::where('subscription_number', $candidate)->exists());

        return $candidate;
    }

    /*
    |============================================================================
    |============================================================================
    |                               Relations
    |============================================================================
    |============================================================================
    */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // الفترات
    public function periods()
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }

    // الفترة الحالية
    public function currentPeriod()
    {
        return $this->hasOne(SubscriptionPeriod::class)->where('is_current', true)->latest('period_start');
    }

    public function seats()
    {
        return $this->hasMany(LicenseSeat::class, 'subscription_id');
    }

    /*
    |============================================================================
    |============================================================================
    |                            Accessors
    |============================================================================
    |============================================================================
    */
    // is_expired
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED;
    }

    // هل الاشتراك معلق
    public function getIsSuspendedAttribute(): bool
    {
        return $this->status === SubscriptionStatus::SUSPENDED;
    }

    // هل الاشتراك معلق
    public function getIsCanceledAttribute(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED;
    }

    // هل الاشتراك معلق
    public function getIsTrialAttribute(): bool
    {
        return $this->status === SubscriptionStatus::TRIAL;
    }

    // formatted_end_date
    public function getFormattedEndDateAttribute()
    {
        return $this->current_period_end
            ? $this->current_period_end->format('Y/m/d')
            : 'N/A';
    }

    // days_remaining
    public function getDaysRemainingAttribute()
    {
        if (! $this->current_period_end) {
            return null;
        }

        return Carbon::now()->diffInDays($this->current_period_end, false);
    }

    // اجمالي المقاعد
    public function getSeatsTotalAttribute(): int
    {
        return (int) $this->licenses_quota;
    }

    /*
    |============================================================================
    |============================================================================
    |                            scopes
    |============================================================================
    |============================================================================
    */
    public function scopeForCompany($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRED);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isTrialExpired(): bool
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isPast();
    }

    public function scopeForLicensing($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /*
    |============================================================================
    |============================================================================
    |                            Helper
    |============================================================================
    |============================================================================
    */
}
