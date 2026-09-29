<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallbackDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'callback_details';

    protected $fillable = [
        'contact_result_id',
        'callback_date',
        'callback_reason',
        'callback_reason_other'
    ];

    protected $casts = [
        'callback_date' => 'datetime',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }
}
