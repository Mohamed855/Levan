<?php

namespace App\Traits\Tenants;

use App\Traits\Tenants\Scopes\UserBranchScope;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToUserBranch
{
    /*
    |--------------------------------------------------------------------------
    | Boot trait and apply global scope
    |--------------------------------------------------------------------------
    */
    protected static function bootBelongsToUserBranch(): void
    {
        static::addGlobalScope(new UserBranchScope());
    }

    /*
    |--------------------------------------------------------------------------
    | Get user relation name for filtering
    |--------------------------------------------------------------------------
    */
    public function getUserRelationName(): string
    {
        return 'user';
    }

    /*
    |--------------------------------------------------------------------------
    | Filter by specific team
    |--------------------------------------------------------------------------
    */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        $relationName = $this->getUserRelationName();

        return $query->whereHas($relationName, function ($q) use ($teamId) {
            $q->where('team_id', $teamId);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Filter by team member IDs
    |--------------------------------------------------------------------------
    */
    public function scopeForTeamMembers(Builder $query, array $memberIds): Builder
    {
        $relationName = $this->getUserRelationName();

        return $query->whereHas($relationName, function ($q) use ($memberIds) {
            $q->whereIn('id', $memberIds);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Disable scope temporarily
    |--------------------------------------------------------------------------
    */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserBranchScope::class);
    }

    public function scopeAllBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserBranchScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Get team name from related user
    |--------------------------------------------------------------------------
    */
    public function getTeamNameAttribute(): string
    {
        $relationName = $this->getUserRelationName();

        if (!method_exists($this, $relationName)) {
            return 'غير محدد';
        }

        $user = $this->$relationName;

        return $user?->team?->name ?? 'غير محدد';
    }
}
