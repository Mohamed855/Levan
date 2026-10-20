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

    public function scopeForTeamMembers(Builder $query, array $memberIds): Builder
    {
        return $query->whereHas($this->getUserRelationName(), fn ($relation) => $relation->whereIn('id', $memberIds));
    }

    public function scopeWithoutUserAccessScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserAccessScope::class);
    }

}
