<?php

namespace Database\Seeders\admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Database\Seeders\Support\DemoAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run the database seeds.
    |--------------------------------------------------------------------------
    */
    public function run(): void
    {
        $superAdmin = AdminUser::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
            'name'                  => 'مدير النظام',
            'email_verified_at'     => now(),
            'password'              => Hash::make('123456789'),
            'phone'                 => '0123456789',
            'status'                => 'active',
            'is_super_admin'        => true,
            ...(config('seeding.test_seeders_enabled', false) ? ['image' => DemoAssets::image('users/demo-superadmin.jpg', 'public')] : []),
            ...(config('seeding.test_seeders_enabled', false) ? ['background_image' => DemoAssets::image('users/demo-superadmin-background.jpg', 'public')] : []),

            'created_at'            => now(),
            'updated_at'            => now(),
            ]
        );

        $superAdmin->syncRoles(['SuperAdmin']);
        $superAdmin->syncPermissions(Permission::where("guard_name", "admin")->get());
    }
}
