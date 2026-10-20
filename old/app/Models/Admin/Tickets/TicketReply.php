<?php

namespace App\Models\Admin\Tickets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'ticket_replies';

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'rating' => 'integer',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
