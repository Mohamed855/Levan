<?php

namespace App\Models\Admin\Cpanel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpanelAccount extends Model
{
    use HasFactory;
    protected $table = 'cpanel_accounts';
    protected $guarded = [];
}
