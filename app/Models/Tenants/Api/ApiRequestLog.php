<?php

namespace App\Models\Tenants\Api;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'method',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
