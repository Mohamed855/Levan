<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemporaryContactLossDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'temporary_contact_loss_details';

    protected $fillable = [
        'contact_result_id',
        'temporary_contact_reason',
        'temporary_contact_reason_other',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }

    // خيارات السبب
    public static function getReasonOptions()
    {
        return [
            'debt_amount_objection' => 'اعتراض على مبلغ المديونية',
            'fraud_operation' => 'عملية احتيال',
            'previous_complaint' => 'شكوى سابقة',
            'other' => 'أخرى',
        ];
    }
}
