<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FullPaymentDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'full_payment_details';

    protected $fillable = [
        'contact_result_id',
        'payment_amount',
        'payment_date',
        'payment_type',
        'collection_type',
        'payment_method_id',
        'payment_receipt_path',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
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
