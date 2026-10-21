<?php

declare(strict_types=1);

namespace App\Models\Admin\Plans;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'plan_type', 'color', 'description', 'price', 'discount_percentage', 'limits_mode', 'included_users', 'properties_limit', 'storage_quota_gb', 'active_auctions_limit', 'domain_management_enabled', 'features', 'is_active', 'sort_order', 'subscription_billing_cycle', 'subscription_duration_type', 'available_from'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'discount_percentage' => 'decimal:2', 'features' => 'array', 'is_active' => 'boolean', 'available_from' => 'datetime', 'domain_management_enabled' => 'boolean'];
    }

    public function getPriceAfterDiscountAttribute(): float
    {
        return max(0, (float) $this->price * (1 - ((float) $this->discount_percentage / 100)));
    }

    public function isUnlimited(): bool { return $this->limits_mode === 'unlimited'; }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(static fn (Builder $query) => $query->whereNull('available_from')->orWhere('available_from', '<=', now()))
            ->where(static fn (Builder $query) => $query->whereNull('code')->orWhere('code', '!=', 'trial_7_days'));
    }
}
