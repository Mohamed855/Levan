<?php

namespace App\Models\Admin\Tenants;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    protected $guarded = [];

    protected $casts = [

        'is_active' => 'boolean',

    ];
}
