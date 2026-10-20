<?php

declare(strict_types=1);

namespace App\Models\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'message',
        'status',
        'reply',
        'replied_at',
        'replied_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactRequestStatus::class,
            'replied_at' => 'datetime',
        ];
    }
}
