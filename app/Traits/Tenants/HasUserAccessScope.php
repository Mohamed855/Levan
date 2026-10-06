<?php

namespace App\Traits\Tenants;

use App\Traits\Tenants\Scopes\UserAccessScope;
use Illuminate\Database\Eloquent\Builder;

trait HasUserAccessScope
{
    protected static function bootHasUserAccessScope(): void
    {
        static::addGlobalScope(new UserAccessScope());
    }

    public function getUserRelationName(): string
    {
        return 'user';
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->whereHas($this->getUserRelationName(), fn ($relation) => $relation->where('team_id', $teamId));
    }

    public function scopeForTeamMembers(Builder $query, array $memberIds): Builder
    {
        return $query->whereHas($this->getUserRelationName(), fn ($relation) => $relation->whereIn('id', $memberIds));
    }

    public function scopeWithoutUserAccessScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserAccessScope::class);
    }

    public function getTeamNameAttribute(): string
    {
        $relation = $this->getUserRelationName();

        return method_exists($this, $relation) ? ($this->$relation?->team?->name ?? 'غير محدد') : 'غير محدد';
    }
}
