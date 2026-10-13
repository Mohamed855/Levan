<?php

namespace App\Models\Tenants\GeneralSetting\PaymentMethod;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsPaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'settings_payment_methods';

    protected $fillable = [
        'name',
        'status',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | for get user add payment method
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
