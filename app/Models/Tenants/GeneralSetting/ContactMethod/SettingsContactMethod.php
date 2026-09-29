<?php

namespace App\Models\Tenants\GeneralSetting\ContactMethod;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsContactMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'settings_contact_methods';

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
    | for get user add contact method
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | for validation delete - if needed for future relationships
    |--------------------------------------------------------------------------
    */
    // إذا كانت وسائل الاتصال مرتبطة بعملاء أو اتصالات في المستقبل
    // public function customerContacts()
    // {
    //     return $this->hasMany(CustomerContact::class, 'contact_method_id');
    // }

    // public function communications()
    // {
    //     return $this->hasMany(Communication::class, 'contact_method_id');
    // }
}
