<?php

namespace App\Services\Admin\AdminMangment\Users;

use App\Enums\Admin\Users\AdminUserStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class UserService
{
    public function getProfileData(AdminUser $user): array
    {
        $user->loadMissing('roles');

        return [
            'user' => $user,
            'role' => $user->roles->first(),
            'recentActivities' => Activity::query()
                ->where('causer_type', $user->getMorphClass())
                ->where('causer_id', $user->getKey())
                ->latest()->take(5)->get(),
            'coupons' => Coupon::query()->where('admin_user_id', $user->getKey())->latest()->get(),
        ];
    }

    public function create(array $data): AdminUser
    {
        $rawPassword = $data['password'] ?? null;

        $user = DB::transaction(function () use ($data) {

            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            // Hash password
            $data['password'] = Hash::make($data['password']);

            // Set default status
            $data['status'] = $data['status'] ?? 'active';

            // Create user
            return AdminUser::create($data);
        });

        try {
            $loginUrl = route('admin.login');
            \App\Services\Shared\Mail\SystemMailService::sendUserWelcome(
                $user->email,
                $user->name,
                $rawPassword,
                $loginUrl,
                'لوحة الإدارة المركزية'
            );
        } catch (\Throwable $e) {
            // Ignore email error to not prevent user creation
        }

        return $user;
    }

    public function update(AdminUser $user, array $data): AdminUser
    {
        return DB::transaction(function () use ($user, $data) {
            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Delete old image
                if ($user->image) {
                    $this->deleteImage($user->image);
                }
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            // Hash password if provided
            if (isset($data['password']) && ! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Update user
            $user->update($data);

            return $user->fresh();
        });
    }

    public function updatePassword(AdminUser $user, string $password): void
    {
        DB::transaction(fn () => $user->update(['password' => Hash::make($password)]));
    }

    public function deleteUser(AdminUser $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Delete user image
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            // Delete user
            return (bool) $user->delete();
        });
    }

    public function toggleStatus(AdminUser $user): AdminUserStatus
    {
        return DB::transaction(function () use ($user): AdminUserStatus {
            $status = $user->status === AdminUserStatus::ACTIVE
                ? AdminUserStatus::INACTIVE
                : AdminUserStatus::ACTIVE;
            $user->update(['status' => $status]);

            return $status;
        });
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $users = AdminUser::whereIn('id', $ids)->get();
            $deletedCount = 0;
            foreach ($users as $user) {
                if ($this->deleteUser($user)) {
                    $deletedCount++;
                }
            }

            return $deletedCount;
        });
    }

    public function forceDeleteUser(AdminUser $user): bool
    {
        return DB::transaction(function () use ($user) {
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            return (bool) $user->forceDelete();
        });
    }

    public function getUserStatistics(): array
    {
        return [
            'total' => AdminUser::count(),
            'active' => AdminUser::where('status', AdminUserStatus::ACTIVE)->count(),
            'inactive' => AdminUser::where('status', AdminUserStatus::INACTIVE)->count(),
            'archived' => AdminUser::onlyTrashed()->count(),
            'super_admins' => AdminUser::role('SuperAdmin', 'admin')->count(),
        ];
    }

    /*
    |============================================================================
    |============================================================================
    |                           Private methods
    |============================================================================
    |============================================================================
    */
    private function handleImageUpload(UploadedFile $image): string
    {
        // Generate unique filename
        $filename = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        // Store image
        return $image->storeAs('admin_users', $filename, 'public');
    }

    public function deleteImage(?string $imagePath): bool
    {
        if (! $imagePath) {
            return false;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->delete($imagePath);
        }

        return false;
    }

    private function getUsersQuery()
    {
        return AdminUser::select([
            'id',
            'name',
            'email',
            'phone',
            'status',
            'image',
            'created_at',
            'last_login_at',
        ]);
        // ])->with(['roles:id,name']);
    }
}
