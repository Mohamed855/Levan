<?php

namespace App\Models\Tenants\BusinessCenter\Customers\ImportSession;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportSession extends Model
{
    use HasFactory;

    protected $table = 'import_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'portfolio_id',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'status', 
        'started_at',
        'completed_at',
        'temp_file_path',
        'errors'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'errors' => 'array' 
    ];
}