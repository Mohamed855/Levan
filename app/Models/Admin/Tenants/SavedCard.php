<?php

namespace App\Models\Admin\Tenants;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User  ;
 use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SavedCard extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'card_number',
        'card_holder_name',
        'exp_month',
        'exp_year',
        'card_type',
        'last_four',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // تشفير البيانات الحساسة تلقائياً
    protected function setCardNumberAttribute($value)
    {
        $this->attributes['card_number'] = Crypt::encryptString($value);
        $this->attributes['last_four'] = substr($value, -4);
    }

    protected function getCardNumberAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    protected function setCardHolderNameAttribute($value)
    {
        $this->attributes['card_holder_name'] = Crypt::encryptString($value);
    }

    protected function getCardHolderNameAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    protected function setExpMonthAttribute($value)
    {
        $this->attributes['exp_month'] = Crypt::encryptString($value);
    }

    protected function getExpMonthAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    protected function setExpYearAttribute($value)
    {
        $this->attributes['exp_year'] = Crypt::encryptString($value);
    }

    protected function getExpYearAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    // علاقة مع المستخدم
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // للحصول على رقم البطاقة مقنع
    public function getMaskedCardNumberAttribute()
    {
        return '**** **** **** ' . $this->last_four;
    }
}
