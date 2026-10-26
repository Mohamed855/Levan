<?php

declare(strict_types=1);

namespace App\Models\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\City;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialRequest extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'rejection_reason',
        'processed_at',
        'processed_by',
        'company_name', 'commercial_number', 'tax_number', 'city_id', 'address',
        'password_hash', 'email_verified_at', 'submission_token', 'tenant_id', 'subscription_id',
        'provisioning_status', 'provisioning_error', 'provisioning_started_at',
    ];

    protected $hidden = ['password_hash'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    protected function casts(): array
    {
        return [
            'status' => TrialRequestStatus::class,
            'processed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'provisioning_started_at' => 'datetime',
        ];
    }
}
