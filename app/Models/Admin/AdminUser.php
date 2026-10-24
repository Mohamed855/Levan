<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Database\Factories\AdminUserFactory;
use App\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable, HasRolesAndPermissions;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => \App\Enums\Admin\Users\AdminUserStatus::class,
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('name', 'SuperAdmin');
    }

    public function coupons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Admin\Coupon::class, 'created_by');
    }

    public function getImageAttribute(): ?string
    {
        return null;
    }

    protected static function newFactory(): Factory
    {
        return AdminUserFactory::new();
    }
}
