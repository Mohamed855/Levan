<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentAttempt extends Model
{
    protected $fillable = [
        'bill_id', 'checkout_token', 'provider', 'provider_payment_id', 'payment_method',
        'status', 'amount', 'currency', 'provider_payload', 'receipt_path', 'expires_at',
        'paid_at', 'processed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
