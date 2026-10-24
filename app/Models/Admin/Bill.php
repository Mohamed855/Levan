<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_number',
        'tenant_id',
        'type',
        'amount',
        'settled_amount',
        'payment_method',
        'status',
        'due_at',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type'           => \App\Enums\Admin\Bills\BillType::class,
            'status'         => \App\Enums\Admin\Bills\BillStatus::class,
            'payment_method' => \App\Enums\Admin\Bills\PaymentMethod::class,
            'amount'         => 'decimal:2',
            'settled_amount' => 'decimal:2',
            'due_at'         => 'datetime',
            'paid_at'        => 'datetime',
            'metadata'       => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
