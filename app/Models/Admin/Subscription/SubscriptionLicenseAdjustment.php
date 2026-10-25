<?php

namespace App\Models\Admin\Subscription;

use App\Models\Admin\Bills\Bill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionLicenseAdjustment extends Model
{
    use HasFactory;

    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    protected $fillable = [
        'period_id',
        'bill_id',
        'new_user_license',
        'old_user_license_snapshot',
        'extra_user_license_price_snapshot',
    ];

    /*
    |============================================================================
    |============================================================================
    |                               Relations
    |============================================================================
    |============================================================================
    */
    public function period()
    {
        return $this->belongsTo(SubscriptionPeriod::class, 'period_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
