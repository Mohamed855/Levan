<?php

declare(strict_types=1);

namespace App\Models\Admin\Tenants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'company_settings';

    protected $fillable = [
        'tenant_id',
        'email_notifications_enabled',
        'two_factor_enabled',
        'external_integrations_enabled',
    ];

    protected $casts = [
        'email_notifications_enabled'   => 'boolean',
        'two_factor_enabled'            => 'boolean',
        'external_integrations_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
