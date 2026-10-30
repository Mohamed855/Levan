<?php

namespace App\Services\Tenants\UserManagement\User;

use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralTrialAccessGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserService
{
    /*
    |--------------------------------------------------------------------------
    | User Creation
    |--------------------------------------------------------------------------
    | Handle user creation with all related operations.
    */
    public function createUser(array $data): User
    {
        app(CentralTrialAccessGateway::class)->assertUserCreationAllowed();

        $rawPassword = $data['password'] ?? null;

        $user = DB::transaction(function () use ($data) {
            $roleId = $data['role_id'] ?? null;
            unset($data['role_id']);

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            $data['password'] = Hash::make($data['password']);
            $data['status'] = $data['status'] ?? 'active';

            $user = User::create($data);

            if ($roleId) {
                $role = Role::query()->where('guard_name', 'tenant')->findOrFail($roleId);
                $user->syncRoles([$role->name]);
            }

            return $user;
        });

        try {
            $tenant = tenant();
            $loginUrl = $tenant ? tenant_route_name('login') : config('app.url');
            $roleName = $user->roles->first()?->name;

            \App\Services\Shared\Mail\SystemMailService::sendUserWelcome(
                $user->email,
                $user->name,
                $rawPassword,
                $loginUrl,
                $tenant?->company_name,
                $roleName
            );
        } catch (\Throwable $e) {
            // Ignore email error to not prevent user creation
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | User Update
    |--------------------------------------------------------------------------
    | Handle user update with all related operations.
    */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $roleId = $data['role_id'] ?? null;
            unset($data['role_id']);

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($user->image) {
                    $this->deleteImage($user->image);
                }
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            if (isset($data['password']) && ! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            if ($roleId) {
                $role = Role::query()->where('guard_name', 'tenant')->findOrFail($roleId);
                $user->syncRoles([$role->name]);
            }

            return $user->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | User Deletion
    |--------------------------------------------------------------------------
    | Handle user deletion with cleanup.
    */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Delete user image
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            // Delete user
            return $user->delete();
        });
    }

    public function updateStatus(User $user, string $status): User
    {
        if ($user->isAdmin()) {
            throw new \DomainException('لا يمكن تغيير حالة مدير النظام.');
        }

        return DB::transaction(function () use ($user, $status): User {
            $user->update(['status' => $status]);

            return $user->refresh();
        });
    }

    public function updatePassword(User $user, string $password): void
    {
        DB::transaction(fn () => $user->update(['password' => Hash::make($password)]));
    }

    /*
    |--------------------------------------------------------------------------
    | Image Upload Handling
    |--------------------------------------------------------------------------
    | Handle image upload with proper naming and storage.
    */
    private function handleImageUpload(UploadedFile $image): string
    {
        // Generate unique filename
        $filename = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        // Store image
        return $image->storeAs('users', $filename, 'tenant_public');
    }

    /*
    |--------------------------------------------------------------------------
    | Image Deletion
    |--------------------------------------------------------------------------
    | Delete image from storage.
    */
    private function deleteImage(string $imagePath): bool
    {
        return Storage::disk('tenant_public')->delete($imagePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Statistics
    |--------------------------------------------------------------------------
    | Get user statistics for dashboard.
    */
    public function getUserStatistics(): array
    {
        return [
            'total' => User::accessible()->count(),
            'active' => User::accessible()->where('status', 'active')->count(),
            'inactive' => User::accessible()->where('status', 'inactive')->count(),
            'archived' => User::onlyTrashed()->accessible()->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Image
    |--------------------------------------------------------------------------
    | Update only the user profile image.
    */
    public function updateUserImage(User $user, $image): User
    {
        return DB::transaction(function () use ($user, $image) {
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            $imagePath = $this->handleImageUpload($image);
            $user->update(['image' => $imagePath]);

            return $user->fresh();
        });
    }

    public function updateBackgroundImage(User $user, $image): User
    {
        return DB::transaction(function () use ($user, $image) {
            if ($user->background_image) {
                $this->deleteImage($user->background_image);
            }

            $imagePath = $this->handleImageUpload($image);
            $user->update(['background_image' => $imagePath]);

            return $user->fresh();
        });
    }
}
