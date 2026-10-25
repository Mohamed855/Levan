<?php

namespace App\Models\Admin;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'name',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
