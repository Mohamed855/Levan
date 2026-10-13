<?php

namespace App\Models\Admin\Bills;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionLicenseAdjustment;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $fillable = [
        'bill_number',
        'gateway_payment_id',
        'tenant_id',
        'subscription_id',
        'period_id',
        'amount',
        'original_amount',
        'discount_amount',
        'coupon_id',
        'coupon_code',
        'coupon_type',
        'coupon_value',
        'type',
        'payment_method',
        'status',
        'due_date',
        'paid_at',
        'cancelled_at',
        'invoice_url',
        'settlement_amount', // مبلغ التسوية

        'bank_id',
        'transfer_date',
        'transfer_notes',
        'verified_by',
        'verified_at',

        'reject_reason',
        'metadata',
    ];

    protected $casts = [
        'type' => BillType::class,
        'payment_method' => PaymentMethod::class,
        'status' => BillStatus::class,
        'metadata' => 'array',
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
        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = static::generateBillNumber();
            }
        });
    }

    private static function generateBillNumber(): string
    {
        $prefix = 'INV';
        $yearMonth = date('Ym'); // 202511

        $lastBill = static::whereRaw("bill_number LIKE '{$prefix}-{$yearMonth}-%'")->orderBy('id', 'desc')->first();

        $sequence = 1;
        if ($lastBill && preg_match('/-(\d+)-/', $lastBill->bill_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        $random = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));

        //  INV-202511-0001-A7F3
        return sprintf('%s-%s-%04d-%s', $prefix, $yearMonth, $sequence, $random);
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

    public function verifiedBy()
    {
        return $this->belongsTo(AdminUser::class, 'verified_by');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function period()
    {
        return $this->belongsTo(SubscriptionPeriod::class, 'period_id');
    }

    public function licenseAdjustment()
    {
        return $this->hasOne(SubscriptionLicenseAdjustment::class, 'bill_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
