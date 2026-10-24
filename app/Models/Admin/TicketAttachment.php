<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'tenant_user_id',
        'tenant_user_name',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
