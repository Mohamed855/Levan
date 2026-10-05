<?php

namespace App\Traits\Tenants\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserBranchScope implements Scope
{
    /*
    |--------------------------------------------------------------------------
    | Apply scope to filter data based on user permissions
    |--------------------------------------------------------------------------
    */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        if ($user->hasPermissionTo('dashboard.view')) {
            return;
        }

        if ($user->canManageTeam()) {
            $this->applyTeamFilter($builder, $model, $user);
            return;
        }

        $this->applyUserFilter($builder, $model, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Apply team-based filtering for team leaders
    |--------------------------------------------------------------------------
    */
    private function applyTeamFilter(Builder $builder, Model $model, $user): void
    {
        $teamMemberIds = $user->teamMemberIds();

        if (empty($teamMemberIds)) {
            $this->applyUserFilter($builder, $model, $user);
            return;
        }

        $relationName = $this->getUserRelationName($model);

        $builder->whereHas($relationName, function ($q) use ($teamMemberIds) {
            $q->whereIn('id', $teamMemberIds);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Apply user-based filtering
    |--------------------------------------------------------------------------
    */
    private function applyUserFilter(Builder $builder, Model $model, $user): void
    {
        $relationName = $this->getUserRelationName($model);

        $builder->whereHas($relationName, function ($q) use ($user) {
            $q->where('id', $user->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get the user relation name from the model
    |--------------------------------------------------------------------------
    */
    private function getUserRelationName(Model $model): string
    {
        if (method_exists($model, 'getUserRelationName')) {
            return $model->getUserRelationName();
        }

        return 'user';
    }
}
