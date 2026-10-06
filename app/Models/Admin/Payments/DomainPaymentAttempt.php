<?php

namespace App\Models\Admin\Payments;

use Illuminate\Database\Eloquent\Model;

class DomainPaymentAttempt extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'subscription_id',
        'period_id',
        'operation',
        'domain',
        'epp_code',
        'years',
        'amount',
        'currency',
        'status',
        'gateway_payment_id',
        'metadata',
        'paid_at',
        'processing_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'epp_code' => 'encrypted',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'processing_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
