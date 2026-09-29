<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectionDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'objection_details';
    
    protected $fillable = [
        'contact_result_id',
        'objection_reason',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }
}