<?php

namespace App\Models\Tenants\UserMangment\Team;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Get all members of the team.
    |--------------------------------------------------------------------------
    */
    public function members()
    {
        return $this->hasMany(User::class, 'team_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Get all leaders of the team.
    |--------------------------------------------------------------------------
    */
    public function leaders()
    {
        return $this->hasMany(User::class, 'team_id')
            ->where('is_team_leader', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Get all employees (non-leaders) of the team.
    |--------------------------------------------------------------------------
    */
    public function employees()
    {
        return $this->hasMany(User::class, 'team_id')
            ->where('is_team_leader', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope a query to only include active teams.
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | Check if team is active.
    |--------------------------------------------------------------------------
    */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | Get team leaders count.
    |--------------------------------------------------------------------------
    */
    public function getLeadersCountAttribute(): int
    {
        return $this->leaders()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Get team members count.
    |--------------------------------------------------------------------------
    */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }
}
