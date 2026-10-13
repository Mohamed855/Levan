<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromisePaymentDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promise_payment_details';

    protected $fillable = [
        'contact_result_id',
        'promise_amount',
        'promise_date',
    ];

    protected $casts = [
        'promise_amount' => 'decimal:2',
        'promise_date' => 'date',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }
}
