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
    | Filter by specific branch
    |--------------------------------------------------------------------------
    */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        $relationName = $this->getUserRelationName();

        return $query->whereHas($relationName, function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Filter by multiple branches
    |--------------------------------------------------------------------------
    */
    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        $relationName = $this->getUserRelationName();

        return $query->whereHas($relationName, function ($q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds);
        });
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
    | Disable branch scope temporarily
    |--------------------------------------------------------------------------
    */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserBranchScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Get all records without filtering
    |--------------------------------------------------------------------------
    */
    public function scopeAllBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserBranchScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Get branch name from related user
    |--------------------------------------------------------------------------
    */
    public function getBranchNameAttribute(): string
    {
        $relationName = $this->getUserRelationName();

        if (!method_exists($this, $relationName)) {
            return 'غير محدد';
        }

        $user = $this->$relationName;

        return $user?->branch?->name ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Get branch code from related user
    |--------------------------------------------------------------------------
    */
    public function getBranchCodeAttribute(): ?string
    {
        $relationName = $this->getUserRelationName();

        if (!method_exists($this, $relationName)) {
            return null;
        }

        $user = $this->$relationName;

        return $user?->branch?->code;
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
