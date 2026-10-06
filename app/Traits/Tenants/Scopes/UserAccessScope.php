<?php

namespace App\Traits\Tenants\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserAccessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user || $user->hasPermissionTo('dashboard.view')) {
            return;
        }

        $relation = method_exists($model, 'getUserRelationName') ? $model->getUserRelationName() : 'user';
        $memberIds = $user->canManageTeam() ? $user->teamMemberIds() : [];

        $builder->whereHas($relation, function ($query) use ($memberIds, $user): void {
            $query->whereIn('id', $memberIds !== [] ? $memberIds : [$user->id]);
        });
    }
}
