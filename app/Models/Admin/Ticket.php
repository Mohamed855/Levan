<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'tenant_id',
        'subject',
        'description',
        'department',
        'status',
        'service_enabled',
    ];

    protected function casts(): array
    {
        return [
            'status'          => TicketStatus::class,
            'service_enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }
}
