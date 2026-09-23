<?php

namespace App\Models\Admin\Licenses;

use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class LicenseSeat extends Model
{
    use HasFactory;

    protected   $primaryKey     = 'id';
    public      $incrementing   = false;
    protected   $keyType        = 'string';


    protected $connection = 'mysql';

    protected $fillable = [
        'id',
        'tenant_id',
        'subscription_id',
        'assigned_user_uid',
        'status',
        'assigned_at',
        'meta',
        'is_core'
    ];


    protected $casts = ['assigned_at' => 'datetime', 'meta' => 'array'];

    /*
    |============================================================================
    |============================================================================
    |                            Boot
    |============================================================================
    |============================================================================
    */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }


    /*
    |============================================================================
    |============================================================================
    |                           Relation
    |============================================================================
    |============================================================================
    */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }


    /*
    |============================================================================
    |============================================================================
    |                             Scope
    |============================================================================
    |============================================================================
    */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeFree($query)
    {
        return $query->where('status', 'free')->whereNull('assigned_user_uid');
    }

    public function scopeForUser($query, string $userUid)
    {
        return $query->where('assigned_user_uid', $userUid);
    }


    /*
    |============================================================================
    |============================================================================
    |                          Another methods
    |============================================================================
    |============================================================================
    */
    public function canBeReleased(): bool
    {
        //  التراخيص الأساسية لا يمكن تحريرها أبداً
        if ($this->is_core) {
            return false;
        }

        //  إذا لم يكن مخصصاً أو لا يوجد تاريخ إسناد
        if ($this->status !== 'assigned' || !$this->assigned_at) {
            return true;
        }

        //  التحقق من مرور 30 يوم من آخر إسناد
        return $this->assigned_at->addDays(30)->isPast();
    }

    //  دالة لحساب الأيام المتبقية
    public function daysUntilReleasable(): ?int
    {
        if ($this->is_core || $this->status !== 'assigned' || !$this->assigned_at) {
            return null;
        }

        $releaseDate = $this->assigned_at->addDays(30);

        if ($releaseDate->isPast()) {
            return 0;
        }

        return now()->diffInDays($releaseDate, false);
    }

    //  تاريخ السماح بالتحرير
    public function getReleasableDateAttribute()
    {
        if (!$this->assigned_at) {
            return null;
        }

        return $this->assigned_at->addDays(30);
    }
}
