<?php

namespace App\Models\Admin\Tickets;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tickets';

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'survey_result' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id', 'id')->orderBy('created_at', 'asc');
    }

    public function section()
    {
        return $this->belongsTo(TicketSection::class, 'ticketing_sections_id', 'id');
    }
}
