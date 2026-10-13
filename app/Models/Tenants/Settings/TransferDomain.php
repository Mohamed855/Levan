<?php

namespace App\Models\Tenants\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferDomain extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(TransferDomain::class, 'tenant_id');
    }
}
