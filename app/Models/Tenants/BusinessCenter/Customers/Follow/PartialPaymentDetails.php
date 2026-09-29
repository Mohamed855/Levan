<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartialPaymentDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partial_payment_details';

    protected $fillable = [
        'contact_result_id',
        'partial_payment_amount',
        'partial_payment_date',
        'partial_payment_type',
        'collection_type',
        'payment_method_id',
        'partial_payment_receipt_path',
        'next_partial_payment_date',
    ];

    protected $casts = [
        'partial_payment_amount' => 'decimal:2',
        'partial_payment_date' => 'date',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(SettingsPaymentMethod::class, 'payment_method_id');
    }
}
