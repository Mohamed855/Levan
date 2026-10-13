<?php

namespace App\Models\Tenants\GeneralSetting\Country;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsCountry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'user_id', 'status'];

    protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | Customer Relationship
    |--------------------------------------------------------------------------
    */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'nationality');
    }
}
