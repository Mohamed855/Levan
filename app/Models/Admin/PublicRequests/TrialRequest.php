<?php

declare(strict_types=1);

namespace App\Models\Admin\PublicRequests;

use Illuminate\Database\Eloquent\Model;

class TrialRequest extends Model
{
    protected $fillable = ['customer_name', 'customer_email', 'customer_phone', 'status'];
}
