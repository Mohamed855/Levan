<?php

namespace App\Models\Admin\AdminMangment\Users;

use App\Enums\Admin\Users\AdminUserStatus;
use App\Models\Admin\Coupons\Coupon;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class AdminUser extends Authenticatable
{
    use ActivityLogTrait,
        HasFactory,
        HasRoles,
        Notifiable,
        SoftDeletes {
            hasPermissionTo as protected traitHasPermissionTo;
        }
    use TwoFactorAuthenticatable;

    protected $dates = ['deleted_at'];

    protected string $guard_name = 'admin';

    protected $connection = 'mysql';

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'image',
        'last_login_at',
        'last_login_ip',
        'background_image',
        'profile_picture',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => AdminUserStatus::class,
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',

    ];

    /*
    |============================================================================
    |============================================================================
    |                          Activity Log
    |============================================================================
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'admin_users';
    }

    public static function getModelNameArabic(): string
    {
        return 'مستخدم';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'status' => 'الحالة',
            'image' => 'الصورة',
            'profile_picture' => 'صورة الملف الشخصي',
            'background_image' => 'صورة الخلفية',
        ];
    }

    public static function getIgnoredAttributes(): array
    {
        return [
            'is_super_admin',
        ];
    }

    public static function getValueTranslations(): array
    {
        return [
            'status' => [
                'active' => 'نشط',
                'inactive' => 'غير نشط',
            ],
        ];
    }

    /*
    |============================================================================
    |============================================================================
    |                               Scope
    |============================================================================
    |============================================================================
    */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin || $this->hasRole('SuperAdmin');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'admin_user_id');
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        $imagePath = $this->image ?: $this->profile_picture;

        if (! $imagePath) {
            return asset('static/brand/logos/logo.svg');
        }

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        return Storage::disk('public')->url($imagePath);
    }
}
