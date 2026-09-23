<?php

namespace App\Services\Admin\AdminMangment\Users;


use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{

    public function create(array $data): AdminUser
    {
        return DB::transaction(function () use ($data) {

            // Handle image upload
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            // Hash password
            $data['password']   = Hash::make($data['password']);

            // Set default status
            $data['status']     = $data['status'] ?? 'active';

            // Create user
            return AdminUser::create($data);
        });
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
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Update user
            $user->update($data);

            return $user->fresh();
        });
    }


    public function deleteUser(AdminUser $user): bool
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


    public function getUserStatistics(): array
    {
        $baseQuery = $this->getUsersQuery();

        return [
            'total'     => (clone $baseQuery)->count(),
            'active'    => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive'  => (clone $baseQuery)->where('status', 'inactive')->count(),
            'archived'  => AdminUser::onlyTrashed()->count(),
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
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        // Store image
        return $image->storeAs('admin_users', $filename, 'public');
    }


    private function deleteImage(string $imagePath): bool
    {
        return Storage::disk('public')->delete($imagePath);
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
            'last_login_at'
        ]);
        // ])->with(['roles:id,name']);
    }
}
