<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantApiToken extends Model
{
    protected $fillable = ['tenant_id', 'name', 'token_prefix', 'token_hash', 'last_used_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
