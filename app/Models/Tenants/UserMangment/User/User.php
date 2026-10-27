<?php

namespace App\Models\Tenants\UserMangment\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;
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
    use TwoFactorAuthenticatable;

    // for guard
    protected $connection = 'tenant';

    protected string $guard_name = 'tenant';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'image',
        'last_login_at',
        'last_login_ip',
        'global_uid',
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
            ->setDescriptionForEvent(fn (string $eventName) => $this->getEventDescription($eventName))
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

            return ! empty($changes) ? ' - التغييرات: '.implode(' | ', $changes) : '';
        } catch (\Exception $e) {
            return '';
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
            ? asset('storage/'.$this->image)
            : asset('static/brand/logos/logo.svg');
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


    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */

    /*
    |--------------------------------------------------------------------------
    | Filter users based on current user permissions
    |--------------------------------------------------------------------------
    */
    public function scopeAccessible($query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasPermissionTo('dashboard.view')) {
            return $query;
        }

        return $query->where('id', $user->id);
    }

    /**
     * Send the password reset notification using Levan branded email.
     */
    public function sendPasswordResetNotification($token): void
    {
        try {
            $resetUrl = tenant_route_name('password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()]);
        } catch (\Throwable $e) {
            $resetUrl = url("reset-password/{$token}?email=" . urlencode((string) $this->getEmailForPasswordReset()));
        }

        \App\Services\Shared\Mail\SystemMailService::sendPasswordReset($this->getEmailForPasswordReset(), $resetUrl, $this->name);
    }

    /**
     * Send the email verification notification using Levan branded email.
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            $verifyUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                tenant_route_name('verification.verify'),
                now()->addMinutes(60),
                ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
            );
        } catch (\Throwable $e) {
            $verifyUrl = url("verify-email/{$this->getKey()}/" . sha1($this->getEmailForVerification()));
        }

        \App\Services\Shared\Mail\SystemMailService::sendVerifyEmail($this->getEmailForVerification(), $verifyUrl, $this->name);
    }
}
