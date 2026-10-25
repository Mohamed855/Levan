<?php

declare(strict_types=1);

namespace App\Models\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialRequest extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'rejection_reason',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TrialRequestStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
