<?php

namespace App\Models\Tenants\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | The attributes that are mass assignable.
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'image',
        'office_name',
        'logo_text',
        'operational_regions',
        'report_phone',
        'report_unified_phone',
        'report_social',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'operational_regions' => 'array',
    ];
}
