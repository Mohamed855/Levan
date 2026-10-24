<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CouponRedemption extends Model
{
    protected $fillable = ['coupon_id', 'bill_id', 'discount_amount', 'status', 'expires_at', 'redeemed_at', 'released_at'];

    protected function casts(): array
    {
        return ['discount_amount' => 'decimal:2', 'expires_at' => 'datetime', 'redeemed_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function bill(): BelongsTo { return $this->belongsTo(Bill::class); }
}
