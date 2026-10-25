<?php

namespace Database\Seeders\admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
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

            'created_at'            => now(),
            'updated_at'            => now(),
            ]
        );

        $superAdmin->syncRoles(['SuperAdmin']);
        $superAdmin->syncPermissions(Permission::where("guard_name", "admin")->get());
    }
}
