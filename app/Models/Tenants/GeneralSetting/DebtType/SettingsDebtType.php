<?php

namespace App\Models\Tenants\GeneralSetting\DebtType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsDebtType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'settings_debt_types';

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
    | for get user add debt type
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
