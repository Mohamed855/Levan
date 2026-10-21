<?php

declare(strict_types=1);

namespace App\Models\Admin\PublicRequests;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = ['customer_name', 'customer_email', 'message', 'status'];
}
