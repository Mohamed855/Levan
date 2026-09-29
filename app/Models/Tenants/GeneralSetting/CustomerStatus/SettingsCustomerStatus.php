<?php

namespace App\Models\Tenants\GeneralSetting\CustomerStatus;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsCustomerStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'settings_customer_status';

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
    | Relationships
    |--------------------------------------------------------------------------
    | العلاقات مع النماذج الأخرى في النظام.
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    | نطاقات الاستعلام لتصفية البيانات.
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
