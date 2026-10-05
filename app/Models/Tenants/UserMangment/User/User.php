<?php

namespace App\Models\Tenants\UserMangment\User;

use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\Branch\BranchUser;
use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerComment;
use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerCommentMention;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use App\Models\Tenants\GeneralSetting\Branch\SettingsBranch;
use App\Models\Tenants\UserMangment\Team\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use HasRoles {
        hasPermissionTo as protected traitHasPermissionTo;
    }

    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    use TwoFactorAuthenticatable;

    // for guard
    protected $connection = 'tenant';


    protected string $guard_name = 'tenant';

    protected $dates = ['deleted_at'];


    protected $fillable = [
        'name',
        'employee_number',
        'email',
        'password',
        'phone',
        'branch_id',
        'team_id',
        'is_team_leader',
        'status',
        'image',
        'last_login_at',
        'last_login_ip',
        'global_uid',
        'license_seat_id',
        'current_session_id',

        'background_image',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'microsoft_token_expires' => 'datetime',
        'microsoft_token' => 'encrypted',
        'microsoft_refresh_token' => 'encrypted',
    ];

    /*
    |============================================================================
    |============================================================================
    |                            Booted
    |============================================================================
    |============================================================================
    */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->global_uid)) {
                $user->global_uid = (string) Str::uuid();
            }
        });
    }


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
            ->useLogName('users')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at', 'email_verified_at', 'remember_token']);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Event Descriptions
    |--------------------------------------------------------------------------
    */

    private function getEventDescription(string $eventName): string
    {
        try {
            $userName = $this->name ?? 'مستخدم غير محدد';

            $descriptions = [
                'created' => "تم إنشاء مستخدم جديد: {$userName}",
                'updated' => "تم تحديث بيانات المستخدم: {$userName}",
                'deleted' => "تم حذف المستخدم: {$userName}",
                'restored' => "تم استعادة المستخدم: {$userName}",
            ];

            $description = $descriptions[$eventName] ?? "تم {$eventName} المستخدم: {$userName}";

            if ($eventName === 'updated') {
                $description .= $this->getUpdateDetails();
            }

            return $description;
        } catch (\Exception $e) {
            return "تم {$eventName} مستخدم";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Change Details
    |--------------------------------------------------------------------------
    */

    private function getUpdateDetails(): string
    {
        try {
            $fieldNames = [
                'name' => 'اسم المستخدم',
                'email' => 'البريد الإلكتروني',
                'phone' => 'رقم الهاتف',
                'branch_id' => 'الفرع',
                'team_id' => 'المجموعة',
                'is_team_leader' => 'صفة القيادة',
                'status' => 'حالة المستخدم',
                'image' => 'صورة المستخدم',
            ];

            $changes = [];
            $changedAttributes = $this->getChanges();
            $originalAttributes = $this->getOriginal();

            foreach ($changedAttributes as $field => $newValue) {
                if (in_array($field, ['updated_at', 'created_at', 'password', 'remember_token', 'email_verified_at'])) {
                    continue;
                }

                if (array_key_exists($field, $originalAttributes)) {
                    $oldValue = $originalAttributes[$field];
                    $fieldNameAr = $fieldNames[$field] ?? $field;

                    $oldValueFormatted = $this->formatFieldValue($field, $oldValue);
                    $newValueFormatted = $this->formatFieldValue($field, $newValue);

                    if ($oldValueFormatted !== $newValueFormatted) {
                        $changes[] = "{$fieldNameAr}: من ({$oldValueFormatted}) إلى ({$newValueFormatted})";
                    }
                }
            }

            return !empty($changes) ? " - التغييرات: " . implode(' | ', $changes) : "";
        } catch (\Exception $e) {
            return "";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Field Value Formatting
    |--------------------------------------------------------------------------
    */
    private function formatFieldValue(string $field, $value): string
    {
        try {
            if (is_null($value) || $value === '') {
                return 'غير محدد';
            }

            switch ($field) {
                case 'branch_id':
                    $branch = Branch::find($value);
                    return $branch ? $branch->name : 'غير محدد';
                case 'team_id':
                    $team = Team::find($value);
                    return $team ? $team->name : 'غير محدد';

                case 'is_team_leader':
                    return $value ? 'قائد فريق' : 'عضو عادي';

                case 'status':
                    return $value === 'active' ? 'نشط' : 'غير نشط';

                case 'password':
                    return 'تم تغيير كلمة المرور';

                case 'image':
                    return $value ? 'تم تحديث الصورة' : 'تم حذف الصورة';

                case 'email':
                case 'phone':
                    return $value ?: 'غير محدد';

                default:
                    return (string) $value;
            }
        } catch (\Exception $e) {
            return (string) $value;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : asset('static/brand/logos/logo.svg');
    }

    /////////////////////////////////////////////////////////////////////////

    /**
     * Get the customers managed by this user (as relationship manager).
     */
    public function managedCustomers()
    {
        return $this->hasMany(Customer::class, 'relationship_manager_id');
    }

    /**
     * Get the distribution history for this collector.
     */
    public function distributionHistory()
    {
        return $this->hasMany(CustomerDistribution::class, 'collector_id');
    }

    /**
     * Get distributions created by this user.
     */
    public function createdDistributions()
    {
        return $this->hasMany(CustomerDistribution::class, 'created_by');
    }


    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */
    public function collectionProcessSessions()
    {
        return $this->hasMany(CollectionProcessSession::class);
    }

    /**
     * Get collector workload statistics.
     */
    public function getCollectorWorkloadAttribute(): array
    {
        if (!$this->hasRole('collector')) {
            return [
                'customers_count' => 0,
                'total_debt' => 0,
                'distributions_count' => 0,
            ];
        }

        return [
            'customers_count' => $this->managedCustomers()->count(),
            'total_debt' => $this->managedCustomers()->sum('debt_amount'),
            'distributions_count' => $this->distributionHistory()->count(),
            'last_distribution' => $this->distributionHistory()->latest()->first(),
        ];
    }
    ///////////////////////////////////////////////////////////////////////////////////

    /*
    |--------------------------------------------------------------------------
    | Accessor للرقم الوظيفي - تحويل للأحرف الكبيرة
    |--------------------------------------------------------------------------
    */
    public function getEmployeeNumberAttribute($value)
    {
        return strtoupper($value);
    }

    /*
    |--------------------------------------------------------------------------
    | Mutator للرقم الوظيفي - حفظ بالأحرف الكبيرة
    |--------------------------------------------------------------------------
    */
    public function setEmployeeNumberAttribute($value)
    {
        $this->attributes['employee_number'] = strtoupper(trim($value));
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Permission Check
    |--------------------------------------------------------------------------
    */
    public function comments(): HasMany
    {
        return $this->hasMany(CustomerComment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Mentions Relationship
    |--------------------------------------------------------------------------
    */
    public function mentions(): HasMany
    {
        return $this->hasMany(CustomerCommentMention::class, 'mentioned_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | For Mentions: Check if user can be mentioned
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    /*
    |--------------------------------------------------------------------------
    | Scope In My Branch
    |--------------------------------------------------------------------------
     | فلترة المستخدمين حسب فرع المستخدم الحالي
    */
    // public function scopeInMyBranch($query)
    // {
    //     $user = auth()->user();

    //     if (!$user) {
    //         return $query;
    //     }

    //     if ($user->hasPermissionTo('dashboard.view')) {
    //         return $query;
    //     }

    //     $allowedBranches = $user->accessible_branch_ids;

    //     if (!empty($allowedBranches)) {
    //         return $query->whereIn('users.branch_id', $allowedBranches);
    //     }

    //     return $query;
    // }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Additional Branch Access Relationship
    |--------------------------------------------------------------------------
    | الفروع الإضافية المسموح للمستخدم الاطلاع عليها
    */
    public function additionalBranches()
    {
        return $this->belongsToMany(
            Branch::class,
            'user_branch_access',
            'user_id',
            'branch_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Accessible Branch IDs
    |--------------------------------------------------------------------------
    | جلب جميع IDs الفروع المتاحة للمستخدم (الأساسي + الإضافية)
    */
    public function getAccessibleBranchIdsAttribute(): array
    {
        $branchIds = [];

        // الفرع الأساسي
        if ($this->branch_id) {
            $branchIds[] = $this->branch_id;
        }

        // الفروع الإضافية
        $additionalBranchIds = $this->additionalBranches()
            ->pluck('branches.id')
            ->toArray();

        return array_unique(array_merge($branchIds, $additionalBranchIds));
    }

    /*
    |--------------------------------------------------------------------------
    | Check Branch Access
    |--------------------------------------------------------------------------
    | التحقق من وصول المستخدم لفرع معين
    */
    public function hasAccessToBranch(int $branchId): bool
    {
        return in_array($branchId, $this->accessible_branch_ids);
    }


    /*
    |--------------------------------------------------------------------------
    | Team Relationship
    |--------------------------------------------------------------------------
    */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Team Helper Methods
    |--------------------------------------------------------------------------
    */
    public function isTeamLeader(): bool
    {
        return $this->is_team_leader && $this->team_id !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Check if User has a Team
    |--------------------------------------------------------------------------
    */
    public function hasTeam(): bool
    {
        return $this->team_id !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Check if user can manage team data
    |--------------------------------------------------------------------------
    */
    public function canManageTeam(): bool
    {
        if (!$this->isTeamLeader() || !$this->hasTeam()) {
            return false;
        }

        try {
            return $this->hasPermissionTo('teams.manage');
        } catch (\Throwable) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get all team member IDs including the leader
    |--------------------------------------------------------------------------
    */
    public function teamMemberIds(): array
    {
        if (!$this->hasTeam()) {
            return [];
        }

        return User::where('team_id', $this->team_id)
            ->pluck('id')
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Filter users based on current user permissions
    |--------------------------------------------------------------------------
    */
    public function scopeAccessible($query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query;
        }

        if ($user->hasPermissionTo('dashboard.view')) {
            return $query;
        }

        if ($user->canManageTeam()) {
            return $query->where('team_id', $user->team_id);
        }

        $allowedBranches = $user->accessible_branch_ids;

        if (!empty($allowedBranches)) {
            return $query->whereIn('branch_id', $allowedBranches);
        }

        return $query;
    }
}
