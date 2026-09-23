<?php

namespace App\Traits\Tenants;

use App\Models\Tenants\Branch\Branch;
use App\Traits\Tenants\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| Has Branch Trait
|--------------------------------------------------------------------------
| للموديلات التي لها branch_id مباشر في جدولها
*/
trait HasBranch
{
    /*
    |--------------------------------------------------------------------------
    | Boot Method
    |--------------------------------------------------------------------------
    | تطبيق Global Scope وتعيين branch_id تلقائياً
    */
    protected static function bootHasBranch(): void
    {
        static::addGlobalScope(new BranchScope());
        
        static::creating(function ($model) {
            if ($model instanceof \App\Models\Tenants\UserMangment\User\User) {
                return;
            }
            
            if (!$model->branch_id && auth()->check() && auth()->user()->branch_id) {
                $model->branch_id = auth()->user()->branch_id;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Relationship
    |--------------------------------------------------------------------------
    | علاقة الفرع
    */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope For Branch
    |--------------------------------------------------------------------------
    | فلترة حسب فرع محدد
    */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }


    /*
    |--------------------------------------------------------------------------
    | Scope Without Branch Scope
    |--------------------------------------------------------------------------
    | تعطيل فلترة الفرع مؤقتاً
    */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope All Branches
    |--------------------------------------------------------------------------
    | عرض جميع السجلات بدون فلترة
    */
    public function scopeAllBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Can Access
    |--------------------------------------------------------------------------
    | التحقق من إمكانية الوصول للسجل
    */
    public function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->hasPermissionTo('لوحة تحكم الإدارة')) {
            return true;
        }

        return $this->branch_id === $user->branch_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Is In User Branch
    |--------------------------------------------------------------------------
    | التحقق من انتماء السجل لفرع المستخدم
    */
    public function isInUserBranch(): bool
    {
        $user = auth()->user();

        if (!$user || !$user->branch_id) {
            return false;
        }

        return $this->branch_id === $user->branch_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Branch Name Attribute
    |--------------------------------------------------------------------------
    | الحصول على اسم الفرع
    */
    public function getBranchNameAttribute(): string
    {
        return $this->branch?->name ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Branch Code Attribute
    |--------------------------------------------------------------------------
    | الحصول على كود الفرع
    */
    public function getBranchCodeAttribute(): ?string
    {
        return $this->branch?->code;
    }
}