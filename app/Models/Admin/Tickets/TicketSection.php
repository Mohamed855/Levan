<?php

namespace App\Models\Admin\Tickets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSection extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'ticket_sections';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticketing_sections_id');
    }
}
