<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recovery_details';

    protected $fillable = [
        'contact_result_id',
        'recovery_reason',
        'recovery_reason_other',
        'recovery_document_path'
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }
}
