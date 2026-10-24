<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ManagedDomain extends Model
{
    protected $fillable = [
        'tenant_id', 'domain', 'type', 'status', 'is_active', 'verification_token', 'verified_at',
        'expires_at', 'registrar', 'registrar_order_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'verified_at' => 'datetime', 'expires_at' => 'datetime',
            'verification_token' => 'encrypted', 'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
