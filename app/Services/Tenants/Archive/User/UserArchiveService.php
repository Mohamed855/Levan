<?php

namespace App\Services\Tenants\Archive\User;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserArchiveService
{
    /*
    |--------------------------------------------------------------------------
    | Restore User
    |--------------------------------------------------------------------------
    | Handle user restoration with all related operations.
    */
    public function restoreUser(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user->restore();
            return $user->fresh(['roles']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete User
    |--------------------------------------------------------------------------
    | Handle user permanent deletion with cleanup.
    */
    public function forceDeleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            if ($user->image && Storage::disk('tenant_public')->exists($user->image)) {
                Storage::disk('tenant_public')->delete($user->image);
            }

            $user->roles()->detach();
            $user->permissions()->detach();

            return $user->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Base Archived Users Query
    |--------------------------------------------------------------------------
    | Get base query for archived users with selected columns.
    */
    public function getArchivedUsersQuery(): Builder
    {
        return User::onlyTrashed()
            ->select(['id', 'name', 'employee_number', 'email', 'phone', 'status', 'image', 'deleted_at', 'created_at'])
            ->with(['roles:id,name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Archived User Statistics
    |--------------------------------------------------------------------------
    | Get archived user statistics for dashboard.
    */
    public function getArchivedUserStatistics(): array
    {
        $baseQuery = $this->getArchivedUsersQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'recently_archived' => (clone $baseQuery)
                ->where('deleted_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Archived Users Query
    |--------------------------------------------------------------------------
    | Apply filters to archived users query.
    */
    public function filterArchivedUsersQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore Users
    |--------------------------------------------------------------------------
    | Restore multiple users at once.
    */
    public function massRestoreUsers(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $users = User::onlyTrashed()->whereIn('id', $userIds)->get();
            
            foreach ($users as $user) {
                $user->restore();
            }

            return $users->count();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete Users
    |--------------------------------------------------------------------------
    | Permanently delete multiple users at once.
    */
    public function massForceDeleteUsers(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $users = User::onlyTrashed()->whereIn('id', $userIds)->get();
            
            foreach ($users as $user) {
                if ($user->image && Storage::disk('tenant_public')->exists($user->image)) {
                    Storage::disk('tenant_public')->delete($user->image);
                }
                
                $user->roles()->detach();
                $user->permissions()->detach();
                $user->forceDelete();
            }

            return $users->count();
        });
    }
}