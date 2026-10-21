<?php

declare(strict_types=1);
namespace App\Models\Access;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Role extends Model
{
    protected $fillable = ['guard', 'name', 'display_name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Tenant\User::class, 'role_user', 'role_id', 'user_id')
            ->wherePivot('guard', 'tenant');
    }

    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Admin\AdminUser::class, 'role_user', 'role_id', 'user_id')
            ->wherePivot('guard', 'admin');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Admin\AdminUser::class, 'role_user', 'role_id', 'user_id')
            ->wherePivot('guard', 'admin');
    }
}
