<?php

declare(strict_types=1);

namespace App\Models\Admin\Tenants;

use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $fillable = [
        'id',
        'company_name',
        'commercial_number',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'tax_number',
        'city_id',
        'address',
        'access_type',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'commercial_number',
            'primary_contact_name',
            'primary_contact_email',
            'primary_contact_phone',
            'tax_number',
            'city_id',
            'address',
            'access_type',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function lastSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\City::class);
    }
}
