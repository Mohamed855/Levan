<?php

namespace App\Traits\Tenants\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/*
|--------------------------------------------------------------------------
| Branch Scope
|--------------------------------------------------------------------------
| فلترة تلقائية للبيانات حسب الفرع
*/
class BranchScope implements Scope
{
    /*
    |--------------------------------------------------------------------------
    | Apply Method
    |--------------------------------------------------------------------------
    | تطبيق الفلترة على الاستعلام حسب فرع المستخدم الحالي
    */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        
        if (!$user) {
            return;
        }
        
        if ($user->hasPermissionTo('لوحة تحكم الإدارة')) {
            return;
        }
        
        if (!$user->branch_id) {
            return;
        }
        
        $builder->where($model->getTable() . '.branch_id', $user->branch_id);
    }
}