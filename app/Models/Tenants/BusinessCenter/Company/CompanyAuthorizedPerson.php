<?php

namespace App\Models\Tenants\BusinessCenter\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyAuthorizedPerson extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    | Fillable attributes and casting.
    */
    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'position',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    | Belongs to company.
    */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
