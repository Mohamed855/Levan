<?php

namespace App\Models\Tenants\Branch;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Branch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    protected $connection = 'tenant';

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'address',
        'city',
        'status',
        // 'is_active',
        'created_by',
    ];

    protected $casts = [
        // 'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('branches')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Event Descriptions
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $branchName = $this->name ?? 'فرع غير محدد';

        return match ($eventName) {
            'created' => "تم إنشاء فرع جديد: {$branchName}",
            'updated' => "تم تحديث بيانات الفرع: {$branchName}",
            'deleted' => "تم حذف الفرع: {$branchName}",
            'restored' => "تم استعادة الفرع: {$branchName}",
            default => "تم {$eventName} الفرع: {$branchName}",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | من قام بإنشاء الفرع
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    // public function scopeActive(Builder $query): Builder
    // {
    //     return $query->where('is_active', true);
    // }

    /*
    |--------------------------------------------------------------------------
    | البحث في الفروع
    |--------------------------------------------------------------------------
    */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%")
                ->orWhere('city', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mutators
    |--------------------------------------------------------------------------
    */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value ? strtoupper(trim($value)) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | تنظيف رقم الهاتف
    |--------------------------------------------------------------------------
    */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value ? preg_replace('/[^0-9+]/', '', $value) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Additional Users With Access
    |--------------------------------------------------------------------------
    | المستخدمون الذين لديهم وصول إضافي لهذا الفرع
    */
    public function additionalUsers()
    {
        return $this->belongsToMany(
            User::class,
            'user_branch_access',
            'branch_id',
            'user_id'
        )->withTimestamps();
    }
}
